<?php

namespace App\Livewire;

use App\Models\AppSetting;
use App\Models\PendingSubscription;
use App\Models\Plan;
use App\Models\RadAcct;
use App\Models\Router;
use App\Models\Transaction;
use App\Services\PlanFilterService;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AppDashboard extends Component
{
    use WithPagination;
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

    // Account sub-views
    public bool   $historyMode = false;
    public bool   $sessionMode = false;
    public bool   $subMode     = false;
    public string $subUserName = '';

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

    public function forceActivate(int $subscriptionId): void
    {
        $user         = Auth::user();
        $subscription = $user->pendingSubscriptions()->find($subscriptionId);
        if (! $subscription) return;

        $rolloverBytes = $user->getRemainingDataAttribute();
        $plan          = $subscription->plan;

        if ($plan->limit_unit === 'Unlimited') {
            $planBytes = null;
        } else {
            $pval      = (int) $plan->data_limit;
            $planBytes = $pval > 1048576
                ? $pval
                : ($plan->limit_unit === 'GB' ? (int) ($pval * 1073741824) : (int) ($pval * 1048576));
        }

        $newLimit = is_null($planBytes) ? null : ($planBytes + ($rolloverBytes ?? 0));

        $update = [
            'plan_id'      => $plan->id,
            'data_limit'   => $newLimit,
            'data_used'    => 0,
            'plan_expiry'  => now()->addDays($plan->validity_days),
            'family_limit' => $plan->family_limit,
        ];

        if ($plan->is_family) {
            $update['is_family_admin'] = true;
            $update['parent_id']       = null;
            \App\Models\User::where('parent_id', $user->id)->update(['parent_id' => null]);
        } else {
            $update['is_family_admin'] = false;
            $update['family_limit']    = null;
        }

        $user->update($update);
        $subscription->delete();
        $user->save(); // triggers RADIUS observer

        $this->syncState();
        $msg = ($rolloverBytes > 0)
            ? 'Plan activated! ' . Number::fileSize($rolloverBytes) . ' rolled over.'
            : 'Plan activated! Tap Connect to get online.';
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    private function resetModes(): void
    {
        $this->historyMode = false;
        $this->sessionMode = false;
        $this->subMode     = false;
        $this->resetPage();
    }

    public function enterHistory(): void  { $this->resetModes(); $this->historyMode = true; }
    public function exitHistory(): void   { $this->resetModes(); }
    public function enterSessions(): void { $this->resetModes(); $this->sessionMode = true; }
    public function exitSessions(): void  { $this->resetModes(); }
    public function enterSubAccounts(): void { $this->resetModes(); $this->subMode = true; }
    public function exitSubAccounts(): void  { $this->resetModes(); $this->subUserName = ''; }

    public function createSubAccount(): void
    {
        $owner = Auth::user();

        if (! $owner->is_family_admin) {
            $this->dispatch('toast', message: 'You need a Family plan to add sub-accounts.', type: 'error');
            return;
        }

        $currentCount = \App\Models\User::where('parent_id', $owner->id)->count();
        if ($currentCount >= ($owner->family_limit ?? 0)) {
            $this->dispatch('toast', message: 'Sub-account limit reached for your plan.', type: 'error');
            return;
        }

        if (! $owner->plan_expiry || $owner->plan_expiry->isPast()) {
            $this->dispatch('toast', message: 'You need an active plan to add sub-accounts.', type: 'error');
            return;
        }

        $this->validate(['subUserName' => ['nullable', 'string', 'max:60']]);

        $words = [
            'sun','red','blue','sky','fast','gold','cool','star','fire','ace',
            'ice','top','max','pro','big','hot','oak','sea','air','bay',
            'dry','gem','jet','key','low','nut','owl','ray','tan','van',
        ];
        $username = '';
        $attempts = 0;
        do {
            $username = $words[array_rand($words)] . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $attempts++;
        } while (\App\Models\User::where('username', $username)->exists() && $attempts < 20);

        $password = Str::random(8);

        \App\Models\User::create([
            'name'            => $this->subUserName ?: $username,
            'username'        => $username,
            'radius_password' => $password,
            'parent_id'       => $owner->id,
            'router_id'       => $owner->router_id,
            'email'           => $username . '@sub.local',
            'password'        => Hash::make($password),
            'plan_id'         => $owner->plan_id,
        ]);

        \App\Models\RadCheck::updateOrCreate(
            ['username' => $username, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $password]
        );
        \App\Models\RadCheck::updateOrCreate(
            ['username' => $username, 'attribute' => 'Simultaneous-Use'],
            ['op' => ':=', 'value' => '2']
        );
        \App\Models\RadCheck::where('username', $username)->where('attribute', 'Expiration')->delete();

        $this->subUserName = '';
        $this->dispatch('toast', message: "Account created — {$username} / {$password}", type: 'success');
    }

    public function deleteSubAccount(int $subId): void
    {
        $owner = Auth::user();
        $sub   = \App\Models\User::where('id', $subId)->where('parent_id', $owner->id)->first();
        if (! $sub) return;

        \App\Models\RadCheck::where('username', $sub->username)->delete();
        \App\Models\RadReply::where('username', $sub->username)->delete();

        DB::table('radacct')
            ->where('username', $sub->username)
            ->whereNull('acctstoptime')
            ->update(['acctstoptime' => now(), 'acctterminatecause' => 'Admin-Reset']);

        $sub->delete();
        $this->dispatch('toast', message: "Sub-account removed.", type: 'success');
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

        $pendingSubscriptions = $user->pendingSubscriptions()->with('plan')->get();

        // Featured plans (Hot Deals)
        $featuredPlans = Plan::where('is_featured', true)
            ->where('is_active', true)
            ->where('is_admin_only', false)
            ->orderBy('sort_order')
            ->get();

        // Session history — always loaded so Alpine can show/hide the panel without a wire: call
        $sessionHistory = $user->username
            ? RadAcct::where('username', $user->username)
                ->latest('acctstarttime')
                ->paginate(10, ['*'], 'sess')
            : null;

        // Sub-accounts (family admins only)
        $subAccounts = $user->is_family_admin
            ? \App\Models\User::where('parent_id', $user->id)
                ->select(['id', 'name', 'username', 'radius_password'])
                ->get()
                ->map(function ($sub) {
                    $online = false;
                    try {
                        $online = RadAcct::where('username', $sub->username)->whereNull('acctstoptime')->exists();
                    } catch (\Exception) {}
                    return ['id' => $sub->id, 'name' => $sub->name, 'username' => $sub->username, 'password' => $sub->radius_password, 'online' => $online];
                })
            : collect();

        // Router ownership (earnings)
        $ownedRouter  = Router::where('owner_id', $user->id)->first();
        $routerStats  = null;
        if ($ownedRouter) {
            try {
                $activeCount  = $ownedRouter->activeSessions()->distinct('username')->count();
                $todayBytes   = (int) $ownedRouter->sessions()->whereDate('acctstarttime', today())->sum(DB::raw('COALESCE(acctinputoctets,0)+COALESCE(acctoutputoctets,0)'));
                $monthBytes   = (int) $ownedRouter->sessions()->where('acctstarttime', '>=', now()->startOfMonth())->sum(DB::raw('COALESCE(acctinputoctets,0)+COALESCE(acctoutputoctets,0)'));
                $totalEarned  = (float) $ownedRouter->payouts()->where('status', 'paid')->sum('amount');
                $pendingPay   = (float) $ownedRouter->payouts()->where('status', 'pending')->sum('amount');
                $routerStats  = compact('activeCount', 'todayBytes', 'monthBytes', 'totalEarned', 'pendingPay');
            } catch (\Exception $e) {
                Log::warning('AppDashboard: router stats error — ' . $e->getMessage());
            }
        }

        // Active devices (open RADIUS sessions for this user)
        $activeDevices = collect();
        try {
            if ($user->username) {
                $activeDevices = RadAcct::where('username', $user->username)
                    ->whereNull('acctstoptime')
                    ->orderBy('acctstarttime', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {
            Log::warning('AppDashboard: activeDevices error — ' . $e->getMessage());
        }

        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with('plan')
            ->latest()
            ->limit(5)
            ->get();

        $allTransactions = Transaction::where('user_id', $user->id)
            ->with('plan')
            ->latest()
            ->paginate(12);

        $userRouterId = $user->router_id;

        return view('livewire.app-dashboard', compact(
            'user', 'plans', 'groupedPlans', 'userRouterId', 'activeSession',
            'sessionDownload', 'sessionUpload', 'uptime',
            'dataUsedPct', 'dataRemaining', 'expiryHuman',
            'recentTransactions', 'allTransactions', 'pendingSubscriptions',
            'featuredPlans', 'sessionHistory', 'subAccounts',
            'ownedRouter', 'routerStats', 'activeDevices'
        ))->layout('layouts.app-shell');
    }
}
