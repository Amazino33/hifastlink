<?php

namespace App\Livewire;

use App\Models\AppSetting;
use App\Models\RadAcct;
use App\Models\Router;
use App\Models\Transaction;
use App\Services\PlanFilterService;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AppDashboard extends Component
{
    // 'no-plan' | 'plan-active' | 'connected'
    public string  $connectionState  = 'no-plan';
    public bool    $isOnHotspot      = false;
    public bool    $showWarning      = false;
    public ?string $connectUrl       = null;
    public string  $voucherCode      = '';
    public bool    $voucherLoading   = false;

    // Profile editing
    public string $profileName             = '';
    public string $profilePhone            = '';
    public string $profileEmail            = '';
    public string $currentPassword         = '';
    public string $newPassword             = '';
    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $this->syncState();

        $u = Auth::user();
        $this->profileName  = $u->name  ?? '';
        $this->profilePhone = $u->phone ?? '';
        $this->profileEmail = $u->email ?? '';
    }

    /** Called by wire:poll every 15 s */
    public function pollConnection(): void
    {
        $this->syncState();
        $this->showWarning = false; // auto-dismiss if they connected
    }

    private function syncState(): void
    {
        $user     = Auth::user();
        $clientIp = request()->ip();

        // When hotspot detection is disabled in Network Settings, treat every
        // user as on the hotspot — Connect button always fires and MikroTik
        // handles the actual authentication.
        if (! AppSetting::bool('hotspot_detection_enabled', false)) {
            $this->isOnHotspot = true;
        } else {
            // Prefer the router_id URL parameter that MikroTik includes when it
            // redirects an unauthenticated device to the login page. Store it in
            // session so it survives across page loads without the parameter.
            $routerId = request()->query('router_id');
            if ($routerId) {
                session(['hotspot_router_id' => (int) $routerId]);
            }

            $hotspotRouter = null;

            // 1. Session-based: set when MikroTik redirected here with ?router_id=X
            $sessionRouterId = session('hotspot_router_id');
            if ($sessionRouterId) {
                $hotspotRouter = Router::where('id', $sessionRouterId)
                    ->where('is_active', true)
                    ->first();
            }

            // 2. IP-based fallback: works when the router's WAN IP is in the DB
            if (! $hotspotRouter) {
                $hotspotRouter = Router::where('ip_address', $clientIp)
                    ->where('is_active', true)
                    ->first();

                if ($hotspotRouter) {
                    session(['hotspot_router_id' => $hotspotRouter->id]);
                }
            }

            $this->isOnHotspot = (bool) $hotspotRouter;
        }

        // Active RADIUS session?
        $hasSession = false;
        try {
            $hasSession = RadAcct::where('username', $user->username)
                ->whereNull('acctstoptime')
                ->exists();
        } catch (\Exception $e) {
            Log::warning('AppDashboard: RADIUS unreachable — ' . $e->getMessage());
        }

        // Plan / voucher / unrestricted access?
        $hasPlan = $user->hasUnrestrictedAccess()
            || ($user->plan_expiry && $user->plan_expiry->isFuture());

        $this->connectionState = match (true) {
            $hasSession => 'connected',
            $hasPlan    => 'plan-active',
            default     => 'no-plan',
        };

        // Build the MikroTik captive portal auto-login URL
        $gateway  = env('MIKROTIK_DNS_NAME', 'login.wifi');
        $dest     = rtrim(config('app.url'), '/') . '/home';

        $this->connectUrl = sprintf(
            'http://%s/login?username=%s&password=%s&dst=%s',
            $gateway,
            urlencode((string) ($user->username ?? '')),
            urlencode((string) ($user->radius_password ?? '')),
            urlencode($dest)
        );
    }

    /** Tap connect button */
    public function connect(): mixed
    {
        if (! $this->isOnHotspot) {
            $this->showWarning = true;
            return null;
        }

        // When on hotspot, redirect browser to the captive portal with credentials
        return $this->redirect($this->connectUrl ?? route('app.home'));
    }

    public function dismissWarning(): void
    {
        $this->showWarning = false;
    }

    public function redeemVoucher(): void
    {
        $this->voucherCode = strtoupper(trim($this->voucherCode));

        $this->validate(['voucherCode' => 'required|string|exists:vouchers,code']);

        $voucher = \App\Models\Voucher::where('code', $this->voucherCode)->first();
        $user    = Auth::user();

        // Creator plan check
        if ($voucher->creator) {
            $service = new \App\Services\SubscriptionService();
            if (! $service->canConnectToHotspot($voucher->creator)) {
                $this->addError('voucherCode', "This voucher's plan has expired.");
                return;
            }
        }

        // Atomically claim slot
        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($voucher, $user) {
            $fresh = \App\Models\Voucher::lockForUpdate()->find($voucher->id);
            if ($fresh->used_count >= $fresh->max_uses) return 'limit_reached';
            if (\App\Models\Transaction::where('reference', 'VCH-' . $fresh->code)->where('user_id', $user->id)->exists()) return 'already_redeemed';
            $fresh->increment('used_count');
            if ($fresh->used_count >= $fresh->max_uses) $fresh->update(['is_used' => true]);
            return true;
        });

        if ($result === 'limit_reached')    { $this->addError('voucherCode', 'Voucher has reached its usage limit.'); return; }
        if ($result === 'already_redeemed') { $this->addError('voucherCode', 'You already redeemed this voucher.'); return; }

        // Activate plan (simplified — identical to UserDashboard logic)
        $newPlan = $voucher->plan;
        if (! $newPlan) {
            // Custom duration voucher
            $user->plan_id         = null;
            $user->data_limit      = $voucher->is_unlimited ? null : ($voucher->data_limit_mb ? $voucher->data_limit_mb * 1048576 : null);
            $user->data_used       = 0;
            $user->plan_expiry     = now()->addHours($voucher->duration_hours);
            $user->plan_started_at = now();
            $user->save();
            try { \App\Services\PlanSyncService::syncUserPlan($user); } catch (\Throwable) {}
        } else {
            $pval     = (int) $newPlan->data_limit;
            $planBytes = $newPlan->limit_unit === 'Unlimited' ? null : ($pval > 1048576 ? $pval : ($newPlan->limit_unit === 'GB' ? $pval * 1073741824 : $pval * 1048576));
            $user->plan_id         = $newPlan->id;
            $user->data_limit      = $planBytes;
            $user->data_used       = 0;
            $user->plan_expiry     = now()->addDays($newPlan->validity_days);
            $user->plan_started_at = now();
            $user->family_limit    = $newPlan->family_limit ?? 0;
            $user->save();
        }

        try {
            \App\Models\Transaction::create([
                'user_id'   => $user->id,
                'plan_id'   => $newPlan?->id,
                'amount'    => $newPlan?->price ?? 0,
                'reference' => 'VCH-' . $voucher->code,
                'status'    => 'success',
                'gateway'   => 'voucher',
                'paid_at'   => now(),
                'router_id' => $user->router_id,
            ]);
        } catch (\Exception $e) {
            Log::error('AppDashboard voucher transaction error: ' . $e->getMessage());
        }

        $this->voucherCode = '';
        $this->syncState();
        $this->dispatch('toast', message: 'Plan activated! Tap Connect to get online.', type: 'success');
    }

    public function saveProfile(): void
    {
        $user = Auth::user();
        $this->validate([
            'profileName'  => ['required', 'string', 'max:255'],
            'profilePhone' => ['required', 'string', 'max:20'],
            'profileEmail' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);
        $user->name  = $this->profileName;
        $user->phone = $this->profilePhone;
        if ($this->profileEmail) {
            $user->email = $this->profileEmail;
        }
        $user->save();
        $this->dispatch('toast', message: 'Profile updated.', type: 'success');
        $this->dispatch('profile-saved');
    }

    public function changePassword(): void
    {
        $user = Auth::user();
        $this->validate([
            'currentPassword' => ['required'],
            'newPassword'     => ['required', 'min:4', 'confirmed'],
        ]);
        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Incorrect current password.');
            return;
        }
        $user->password = Hash::make($this->newPassword);
        $user->save();
        $this->currentPassword         = '';
        $this->newPassword             = '';
        $this->newPasswordConfirmation = '';
        $this->dispatch('toast', message: 'Password changed successfully.', type: 'success');
        $this->dispatch('profile-saved');
    }

    public function render()
    {
        $user   = Auth::user();
        $router = $user->router;

        $plans = (new PlanFilterService())
            ->getAvailablePlans($router?->nas_identifier, $router?->id)
            ->filter(fn($p) => ! $p->is_admin_only); // PlanFilterService doesn't strip admin-only plans

        // PlanFilterService returns a base Collection (sortBy strips Eloquent type),
        // so ->load() won't work. Attach routers manually with a single query instead.
        $routerIds = $plans->pluck('router_id')->filter()->unique()->values();
        if ($routerIds->isNotEmpty()) {
            $routers = Router::whereIn('id', $routerIds)->get()->keyBy('id');
            $plans->each(fn($plan) => $plan->setRelation('router', $routers->get($plan->router_id)));
        }

        // Group by duration category; location shown as a badge on each card.
        // Order: Daily → Weekly → Monthly → Family → Long-term
        $groupOrder   = ['Daily', 'Weekly', 'Monthly', 'Family', 'Long-term'];
        $groupedPlans = collect($plans->all())
            ->groupBy(function ($plan) {
                if ($plan->is_family)          return 'Family';
                if ($plan->validity_days <= 1)  return 'Daily';
                if ($plan->validity_days <= 7)  return 'Weekly';
                if ($plan->validity_days <= 31) return 'Monthly';
                return 'Long-term';
            })
            ->sortKeysUsing(fn($a, $b) => array_search($a, ['Daily','Weekly','Monthly','Family','Long-term']) <=> array_search($b, ['Daily','Weekly','Monthly','Family','Long-term']));

        $activeSession   = null;
        $sessionDownload = null;
        $sessionUpload   = null;
        $uptime          = null;
        $dataUsedPct     = 0;
        $dataRemaining   = null;
        $expiryHuman     = null;

        try {
            $activeSession = RadAcct::where('username', $user->username)
                ->whereNull('acctstoptime')
                ->latest('acctstarttime')
                ->first();

            if ($activeSession) {
                $sessionDownload = Number::fileSize((int) ($activeSession->acctoutputoctets ?? 0));
                $sessionUpload   = Number::fileSize((int) ($activeSession->acctinputoctets  ?? 0));
                $secs = is_numeric($activeSession->acctsessiontime) ? (int) $activeSession->acctsessiontime : 0;
                $uptime = $secs > 0
                    ? CarbonInterval::seconds($secs)->cascade()->forHumans(short: true)
                    : now()->diffForHumans(\Carbon\Carbon::parse($activeSession->acctstarttime), true);
            }
        } catch (\Exception $e) {
            Log::warning('AppDashboard render: RADIUS unavailable');
        }

        // Data usage percentage
        if ($user->data_limit && $user->data_limit > 0) {
            $limitBytes    = $user->data_limit <= 1048576 ? $user->data_limit * 1048576 : $user->data_limit;
            $usedBytes     = $user->data_used  <= 1048576 ? $user->data_used  * 1048576 : (int) $user->data_used;
            $dataUsedPct   = (int) min(100, round(($usedBytes / $limitBytes) * 100));
            $remainBytes   = max(0, $limitBytes - $usedBytes);
            $dataRemaining = Number::fileSize($remainBytes);
        } else {
            $dataRemaining = $user->data_limit === null ? 'Unlimited' : 'No Plan';
        }

        if ($user->plan_expiry && $user->plan_expiry->isFuture()) {
            $diff = now()->diffInDays($user->plan_expiry, false);
            $expiryHuman = ($diff < 1)
                ? now()->diffInHours($user->plan_expiry) . 'h left'
                : ceil($diff) . ' day' . (ceil($diff) === 1.0 ? '' : 's') . ' left';
        }

        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with('plan')
            ->latest()
            ->limit(5)
            ->get();

        $userRouterId = $user->router_id;

        return view('livewire.app-dashboard', compact(
            'user', 'plans', 'groupedPlans', 'userRouterId', 'activeSession',
            'sessionDownload', 'sessionUpload', 'uptime',
            'dataUsedPct', 'dataRemaining', 'expiryHuman',
            'recentTransactions'
        ))->layout('layouts.app-shell');
    }
}
