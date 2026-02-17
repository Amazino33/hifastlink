<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Plan;
use App\Models\RadAcct;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voucher;
use Filament\Notifications\Notification;
use App\Services\PlanFilterService;
use App\Models\Device;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;

/**
 * @method void emit(string $event, mixed ...$params)
 * @method void emitTo(string $component, string $event, mixed ...$params)
 * @method void emitSelf(string $event, mixed ...$params)
 * @method void dispatchBrowserEvent(string $event, array $payload = [])
 */
class UserDashboard extends Component
{
    use WithPagination;

    protected $listeners = [
        'subscribeEvent' => 'subscribe',
        'refreshDashboard' => '$refresh',
    ];

    protected $layout = 'layouts.app';

    public $voucherCode = '';
    public $router = null; // URL parameter for router identification

    protected $queryString = [
        'router' => ['as' => 'router'],
    ];

    public function mount()
    {
        $user = Auth::user();

        // Update user's router association if router parameter is provided
        if ($this->router) {
            $router = \App\Models\Router::where('nas_identifier', $this->router)->first();
            \Log::info('UserDashboard router update attempt', [
                'router_identifier' => $this->router,
                'router_found' => $router ? 'yes' : 'no',
                'router_id' => $router ? $router->id : null,
                'user_id' => $user->id,
                'current_router_id' => $user->router_id
            ]);

            if ($router) {
                $user->router_id = $router->id;
                $saved = $user->save();
                \Log::info('User router_id updated in UserDashboard', [
                    'user_id' => $user->id,
                    'new_router_id' => $user->router_id,
                    'saved' => $saved
                ]);
            }
        }
    }

    public function subscribe(int $planId): void
    {
        $plan = Plan::find($planId);

        if (! $plan) {
            Notification::make()
                ->title('Plan not found')
                ->body('The plan you selected could not be found.')
                ->danger()
                ->send();

            return;
        }

        $user = Auth::user();

        // If the user's current plan still has time remaining but no data, expire it immediately
        if ($user->plan_id && $user->plan_expiry && $user->plan_expiry->isFuture()) {
            $remaining = $user->remaining_data ?? 0;
            if ($remaining <= 0) {
                // expire current plan so new plan can activate
                $user->plan_id = null;
                $user->plan_expiry = null;
                $user->save(); // triggers observer -> PlanSyncService
            } else {
                Notification::make()
                    ->title('Subscription Active')
                    ->body('You already have an active plan. Please wait for it to expire.')
                    ->warning()
                    ->send();

                return;
            }
        }

        // Simulate free purchase / immediate activation
        // Convert plan limit to bytes (respecting unit)
        if ($plan->limit_unit === 'Unlimited') {
            $planBytes = null;
        } else {
            $pval = (int) $plan->data_limit;
            if ($pval > 1048576) {
                $planBytes = $pval;
            } else {
                $planBytes = $plan->limit_unit === 'GB' ? (int) ($pval * 1073741824) : (int) ($pval * 1048576);
            }
        }

        $user->plan_id = $plan->id;
        $user->data_used = 0;
        $user->data_limit = is_null($planBytes) ? null : $planBytes;
        $user->plan_expiry = now()->addDays($plan->validity_days ?? 0);
        $user->plan_started_at = now();
        if ($plan->is_family) {
            $user->is_family_admin = true;
            $user->parent_id = null;
            \App\Models\User::where('parent_id', $user->id)->update(['parent_id' => null]);
        } else {
            $user->is_family_admin = false;
            $user->family_limit = null;
        }
        $user->family_limit = $plan->family_limit;
        $user->save(); // triggers observer -> RADIUS sync

        Notification::make()
            ->title('Plan Activated!')
            ->body("You have successfully subscribed to {$plan->name}.")
            ->success()
            ->send();

        // Flash a session message as a fallback for environments where dispatchBrowserEvent() is unavailable
        session()->flash('toast_message', "You have successfully subscribed to {$plan->name}.");

        // Dispatch a browser event if available (safe check to avoid method missing errors)
        if (method_exists($this, 'dispatchBrowserEvent')) {
            $this->dispatchBrowserEvent('plan-activated', [
                'planName' => $plan->name,
                'planId' => $plan->id,
                'message' => "You have successfully subscribed to {$plan->name}."
            ]);
        }
    }

    public function render()
    {
        $user = Auth::user();

        // Get router information from user record, then URL parameter, then session
        $router = null;
        $routerId = null;
        $routerIdentifier = null;

        // Priority 1: User's stored router_id
        if ($user->router_id) {
            $router = $user->router;
            if ($router) {
                $routerId = $router->id;
                $routerIdentifier = $router->nas_identifier;
                \Log::info('UserDashboard: Using user router_id', [
                    'user_id' => $user->id,
                    'router_id' => $user->router_id,
                    'router_identifier' => $routerIdentifier
                ]);
            }
        }

        // Priority 2: URL parameter (can override user's router)
        if ($this->router) {
            $urlRouter = \App\Models\Router::where('nas_identifier', $this->router)->first();
            if ($urlRouter) {
                $router = $urlRouter;
                $routerId = $urlRouter->id;
                $routerIdentifier = $urlRouter->nas_identifier;
                \Log::info('UserDashboard: Using URL router parameter', [
                    'router_param' => $this->router,
                    'router_id' => $routerId,
                    'router_identifier' => $routerIdentifier
                ]);
            }
        }

        // Priority 3: Session fallback
        if (!$routerIdentifier) {
            $routerIdentifier = session('current_router');
            if ($routerIdentifier) {
                $router = \App\Models\Router::where('nas_identifier', $routerIdentifier)->first();
                if ($router) {
                    $routerId = $router->id;
                    \Log::info('UserDashboard: Using session router', [
                        'session_router' => $routerIdentifier,
                        'router_id' => $routerId
                    ]);
                }
            }
        }

        \Log::info('UserDashboard: Final router determination', [
            'user_id' => $user->id,
            'final_router_id' => $routerId,
            'final_router_identifier' => $routerIdentifier,
            'has_router' => $router ? 'yes' : 'no'
        ]);

        // Get available plans based on NAS identifier and router
        $planFilterService = new PlanFilterService();
        $plans = $planFilterService->getAvailablePlans($routerIdentifier, $routerId);

        \Log::info('UserDashboard: Plans retrieved', [
            'user_id' => $user->id,
            'router_identifier' => $routerIdentifier,
            'router_id' => $routerId,
            'plans_count' => $plans->count(),
            'plan_names' => $plans->pluck('name')->toArray()
        ]);

        // Active RADIUS session (acctstoptime NULL - no time restriction)
        $radiusReachable = true;
        try {
            $activeSession = RadAcct::forUser($user->username)
                ->whereNull('acctstoptime')
                ->latest('acctstarttime')
                ->first();

            // If there is no active session but RADIUS has records for this username, log details to help debug
            if (!$activeSession) {
                $hasAny = RadAcct::forUser($user->username)->whereNull('acctstoptime')->exists();
                if ($hasAny) {
                    $last = RadAcct::forUser($user->username)->latest('acctupdatetime')->first();
                    Log::warning('RadAcct has entries for user but none matched active() filter in UserDashboard', [
                        'username' => $user->username,
                        'has_active_rows' => $hasAny,
                        'last_acctupdatetime' => $last?->acctupdatetime?->toDateTimeString(),
                        'last_acctstarttime' => $last?->acctstarttime?->toDateTimeString(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // RADIUS server unreachable - log the error and set session to null
            Log::warning('RADIUS database connection failed in UserDashboard: ' . $e->getMessage());
            $activeSession = null;
            $radiusReachable = false;
        }

        // Live usage from the active session (kept for reference)
        $liveUsage = $activeSession ? (($activeSession->acctinputoctets ?? 0) + ($activeSession->acctoutputoctets ?? 0)) : 0;

        // Determine start date for counting usage (plan start). If missing, fallback to 1 year back to avoid huge scans
        $startDate = $user->plan_started_at ?? now()->subYears(1);

        // Identify the family master ID (parent if exists, else self)
        $masterId = $user->parent_id ?? $user->id;

        // Get the master user with plan loaded
        $masterUser = User::with('plan')->find($masterId);

        // Get all family usernames (master + children)
        $familyUsernames = User::where('id', $masterId)->orWhere('parent_id', $masterId)->pluck('username');

        // Sum all historical sessions for the family since the plan started
        $historyUsage = RadAcct::whereIn('username', $familyUsernames)
            ->where('acctstarttime', '>=', $startDate)
            ->sum(DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)'));

        // Total usage derived from radacct history
        $totalUsed = (int) $historyUsage;
        $formattedTotalUsed = Number::fileSize($totalUsed);

        // Current speed: show plan speed limits when online (use current plan instead of stored plan)
        $currentPlan = $masterUser->current_plan ?? null;
        if ($activeSession && $currentPlan) {
            $upload = $currentPlan->speed_limit_upload ?? 0;
            $download = $currentPlan->speed_limit_download ?? 0;
            $currentSpeed = ($download || $upload) ? ($download . 'k/' . $upload . 'k') : '0 kbps';
        } else {
            $currentSpeed = '0 kbps';
        }

        // MAC capture (from redirect) and device online detection via callingstationid
        if (request()->filled('mac')) {
            session(['current_device_mac' => request()->input('mac')]);
        }

        // Try cookie-based remembered browser first (persistent per-browser token)
        $currentMac = session('current_device_mac');
        if (! $currentMac) {
            $cookieToken = Cookie::get('fastlink_device_token');
            if ($cookieToken) {
                // find a device owned by this user with a matching token hash in meta
                $deviceWithToken = Device::where('user_id', $user->id)->get()->first(function ($d) use ($cookieToken) {
                    $hash = $d->meta['browser_token_hash'] ?? null;
                    return $hash && Hash::check($cookieToken, $hash);
                });

                if ($deviceWithToken) {
                    session(['current_device_mac' => $deviceWithToken->mac]);
                    $currentMac = $deviceWithToken->mac;
                }
            }
        }

        // If still no currentMac, attempt an IP -> radacct lookup (best-effort auto-detect for same physical device)
        if (! $currentMac) {
            try {
                $ip = request()->ip();
                $rad = RadAcct::forUser($user->username)
                    ->active()
                    ->where('framedipaddress', $ip)
                    ->first();

                if ($rad && $rad->callingstationid) {
                    $detectedMac = $rad->callingstationid;
                    session(['current_device_mac' => $detectedMac]);
                    // persist into devices table for UI/record
                    Device::upsertFromLogin($user, $detectedMac, $rad->nasidentifier ?? $rad->nasipaddress, $rad->framedipaddress, request()->userAgent());
                    $currentMac = $detectedMac;
                    Log::info('UserDashboard: auto-detected device by IP -> RadAcct', ['user' => $user->username, 'ip' => $ip, 'mac' => $detectedMac]);
                }
            } catch (\Exception $e) {
                Log::warning('UserDashboard: RadAcct lookup failed for IP auto-detect: ' . $e->getMessage());
            }
        }

        // Auto-claim devices when the detected MAC matches an existing device owned by this user.
        // This will create a persistent browser token for that device (same behavior as manual claim).
        if ($currentMac) {
            try {
                $foundDevice = Device::where('user_id', $user->id)->where('mac', $currentMac)->first();
                if ($foundDevice) {
                    $browserTokenHash = data_get($foundDevice->meta, 'browser_token_hash');
                    $cookieToken = Cookie::get('fastlink_device_token');

                    // If the device already had a token hash but the cookie is missing/mismatched, re-issue a token/hash pair.
                    if ($browserTokenHash) {
                        if (! $cookieToken || ! Hash::check($cookieToken, $browserTokenHash)) {
                            $token = bin2hex(random_bytes(32));
                            $hash = Hash::make($token);
                            $meta = $foundDevice->meta ?? [];
                            $meta['browser_token_hash'] = $hash;
                            $foundDevice->meta = $meta;
                            $foundDevice->save();
                            Cookie::queue(Cookie::make('fastlink_device_token', $token, 60 * 24 * 365, '/', null, true, true, false, 'Lax'));
                            Log::info('UserDashboard: re-issued browser token for matched device', ['user_id' => $user->id, 'device_id' => $foundDevice->id, 'mac' => $currentMac]);
                        }
                    } else {
                        // New auto-claim: generate token + hash and persist
                        $token = bin2hex(random_bytes(32));
                        $hash = Hash::make($token);
                        $meta = $foundDevice->meta ?? [];
                        $meta['browser_token_hash'] = $hash;
                        $foundDevice->meta = $meta;
                        $foundDevice->save();
                        Cookie::queue(Cookie::make('fastlink_device_token', $token, 60 * 24 * 365, '/', null, true, true, false, 'Lax'));
                        Log::info('UserDashboard: auto-claimed device by MAC match', ['user_id' => $user->id, 'device_id' => $foundDevice->id, 'mac' => $currentMac]);
                    }

                    // Ensure session is set to the detected MAC
                    session(['current_device_mac' => $currentMac]);
                }
            } catch (\Exception $e) {
                Log::warning('UserDashboard: auto-claim failed: ' . $e->getMessage());
            }
        }

        // Router identity capture (from redirect): store ?nas_identifier=... in session
        if (request()->has('nas_identifier')) {
            session(['current_router_nas_identifier' => request('nas_identifier')]);
        }

        $activeSessions = RadAcct::where('username', $user->username)
            ->whereNull('acctstoptime')
            ->get();

        $connectedDevices = $activeSessions->count();
        $maxDevices = ($masterUser->plan && $masterUser->plan->max_devices) ? $masterUser->plan->max_devices : 1;

        $isDeviceOnline = false;
        if ($currentMac) {
            $isDeviceOnline = RadAcct::where('username', $user->username)
                ->where('callingstationid', $currentMac)
                ->whereNull('acctstoptime')
                ->exists();
        }
        
        // Get current router location (prefer router identity from session when present)
        $currentLocation = null;
        $currentRouter = null;

        // If a router was already determined earlier (for example via user.router_id or URL param), prefer it
        if (isset($router) && $router) {
            $currentRouter = $router;
            $currentLocation = $router->location ?: ($router->name ?: 'Unknown Location');
        }

        $currentRouterNasIdentifier = session('current_router_nas_identifier');
        if ($currentRouterNasIdentifier) {
            $routerFromParam = \App\Models\Router::where('nas_identifier', $currentRouterNasIdentifier)->first();
            if ($routerFromParam) {
                $currentRouter = $routerFromParam;
                $currentLocation = $routerFromParam->location ?: ($routerFromParam->name ?: 'Unknown Location');
            }
        }

        if (! $currentLocation && $activeSession) {
            // Try to find router by multiple criteria
            $router = null;
            
            // 1. Try by IP address (most direct)
            if ($activeSession->nasipaddress) {
                $router = \App\Models\Router::where('ip_address', $activeSession->nasipaddress)
                    ->where('is_active', true)
                    ->first();
            }
            
            // 2. If not found by IP, try by called_station_id (router identifier)
            if (!$router && $activeSession->calledstationid) {
                $router = \App\Models\Router::where('nas_identifier', $activeSession->calledstationid)
                    ->where('is_active', true)
                    ->first();
            }
            
            // 3. If still not found, look up NAS table shortname and match it
            if (!$router && $activeSession->nasipaddress) {
                $nas = \App\Models\Nas::where('nasname', $activeSession->nasipaddress)->first();
                if ($nas && $nas->shortname) {
                    $router = \App\Models\Router::where('nas_identifier', $nas->shortname)
                        ->where('is_active', true)
                        ->first();
                }
            }
            
            if ($router) {
                // Display router name and location
                $currentLocation = $router->name;
                if ($router->location) {
                    $currentLocation .= ' - ' . $router->location;
                }
                $currentRouter = $router;
            } else {
                // Fallback to IP if router not found in database
                $currentLocation = 'Router: ' . ($activeSession->nasipaddress ?? 'Unknown');
            }
        }

        if (! $currentLocation) {
            $currentLocation = 'Unknown Location';
        }

        // Determine subscription status from the prioritized current plan
        // If plan_id is null, user has NO plan regardless of other factors
        if (!$masterUser->plan_id) {
            $subscriptionStatus = 'inactive';
        } else {
            $subscriptionStatus = $masterUser->current_plan_status ?? 'inactive';
        }
        
        $connectionStatus = ($activeSession) ? 'active' : 'offline';

        // If RADIUS is unreachable, show 'unknown' status
        if (!$radiusReachable) {
            $connectionStatus = 'unknown';
        }
        
        // Override: If no plan_id, force offline status even if session exists
        if (!$masterUser->plan_id) {
            $connectionStatus = 'offline';
        }

        // Determine remaining bytes and check for exhaustion
        $remainingBytes = null;
        $formattedRemaining = null;

        if ($masterUser && $masterUser->plan && $masterUser->plan->limit_unit !== 'Unlimited' && $masterUser->plan->data_limit) {
            $planVal = (int) $masterUser->plan->data_limit;
            if ($planVal > 1048576) {
                $planBytes = $planVal;
            } else {
                $planBytes = $masterUser->plan->limit_unit === 'GB' ? (int) ($planVal * 1073741824) : (int) ($planVal * 1048576);
            }

            // Include rollover in the effective quota only when validity matches the plan (same rule as PlanSyncService)
            $rolloverBytes = (int) ($masterUser->rollover_available_bytes ?? 0);
            $includeRollover = $rolloverBytes > 0 && $masterUser->rollover_validity_days == $masterUser->plan->validity_days;
            $effectivePlanBytes = $includeRollover ? ($planBytes + $rolloverBytes) : $planBytes;

            $remainingBytes = max(0, $effectivePlanBytes - $totalUsed);
            $formattedRemaining = Number::fileSize($remainingBytes);

            // If used >= effective limit, immediately handle exhaustion
            if ($effectivePlanBytes > 0 && $totalUsed >= $effectivePlanBytes && $masterUser->plan_id) {
                Log::info("Data exhausted detected in dashboard for user {$masterUser->username}");

                // Clear plan immediately
                $masterUser->plan_id = null;
                $masterUser->data_plan_id = null;
                $masterUser->plan_expiry = null;
                $masterUser->plan_started_at = null;
                $masterUser->data_limit = 0;
                $masterUser->subscription_start_date = null;
                $masterUser->subscription_end_date = null;
                $masterUser->connection_status = 'exhausted';
                $masterUser->save();

                // Disconnect active sessions from RADIUS
                DB::table('radacct')
                    ->where('username', $masterUser->username)
                    ->whereNull('acctstoptime')
                    ->update([
                        'acctstoptime' => now(),
                        'acctterminatecause' => 'Data-Limit-Exceeded',
                    ]);

                // Remove RADIUS credentials (prevents re-authentication)
                DB::table('radcheck')->where('username', $masterUser->username)->delete();
                DB::table('radreply')->where('username', $masterUser->username)->delete();

                // Attempt to disconnect from MikroTik router via HTTP request
                try {
                    $this->disconnectFromMikroTik($masterUser->username);
                } catch (\Exception $e) {
                    Log::warning("Failed to disconnect user from MikroTik: " . $e->getMessage());
                }

                // Force status update
                $subscriptionStatus = 'inactive';
                $connectionStatus = 'offline';
                $activeSession = null;

                Log::info("Data exhaustion handled immediately for {$masterUser->username}");
            }
        } else {
            $formattedRemaining = 'Unlimited';
            $effectivePlanBytes = null; // unlimited
        }

        // Prefer the framed IP from the active RADIUS session; otherwise show a connected indicator when the device is online
        if ($activeSession && !empty($activeSession->framedipaddress)) {
            $currentIp = $activeSession->framedipaddress;
        } elseif ($isDeviceOnline) {
            // Device is online but framed IP is unavailable; avoid using request()->ip()
            $currentIp = 'Connected';
        } elseif ($connectionStatus === 'unknown') {
            $currentIp = 'Server Unreachable';
        } else {
            $currentIp = 'Offline';
        }

        // Formatted validity string (or 'N/A')
        $validUntil = $masterUser->plan_expiry ? $masterUser->plan_expiry->format('d M Y, h:i A') : 'N/A';
        $planValidityHuman = $masterUser->plan_validity_human;

        // Days remaining (signed diff, clamp to 0)
        if ($masterUser->plan_expiry) {
            $diff = now()->diffInDays($masterUser->plan_expiry, false);
            $subscriptionDays = (int) max(0, $diff);

            if ($subscriptionDays <= 7) {
                $daysBadgeClass = 'bg-red-500 text-white';
            } elseif ($subscriptionDays <= 30) {
                $daysBadgeClass = 'bg-yellow-500 text-black';
            } else {
                $daysBadgeClass = 'bg-green-500 text-white';
            }
        } else {
            $subscriptionDays = 0;
            $daysBadgeClass = 'bg-gray-500 text-white';
        }

        // Calculate data usage percentage based on totalUsed and effective quota (plan + rollover when applicable)
        if (isset($effectivePlanBytes) && $effectivePlanBytes !== null) {
            if ($effectivePlanBytes > 0) {
                $percentage = ($totalUsed / (float) $effectivePlanBytes) * 100;
                $dataUsagePercentage = (int) min(100, round($percentage));
            } else {
                $dataUsagePercentage = 0;
            }
        } else {
            // Unlimited plans or no plan
            $dataUsagePercentage = 0;
        }

        // If there is no active plan, clear usage display and make the UI show 'No Active Plan'
        if ($subscriptionStatus === 'inactive') {
            $formattedTotalUsed = Number::fileSize(0);
            $formattedDataLimit = 'No Active Plan';
            $dataUsagePercentage = 0;
        }

        $uptime = $activeSession && $activeSession->acctstarttime ? Carbon::parse($activeSession->acctstarttime)->diffForHumans() : ($user->last_online ? $user->last_online->diffForHumans() : '-');

        // Fetch all transactions
        $recentTransactions = \App\Models\Transaction::where('user_id', Auth::id())
            ->with('plan')
            ->latest()
            ->paginate(10);

        return view('livewire.user-dashboard', [
            'user' => $user,
            'plans' => $plans,
            'totalUsed' => $totalUsed,
            'formattedTotalUsed' => $formattedTotalUsed,
            'formattedDataLimit' => (isset($effectivePlanBytes) && $effectivePlanBytes !== null) ? Number::fileSize($effectivePlanBytes) : 'Unlimited',
            'subscriptionStatus' => $subscriptionStatus,
            'connectionStatus' => $connectionStatus,
            'currentIp' => $currentIp,
            'validUntil' => $validUntil,
            'planValidityHuman' => $planValidityHuman,
            'subscriptionDays' => $subscriptionDays,
            'dataUsagePercentage' => $dataUsagePercentage,
            'currentSpeed' => $currentSpeed,
            'uptime' => $uptime,
            'daysBadgeClass' => $daysBadgeClass,
            'recentTransactions' => $recentTransactions,
            'radiusReachable' => $radiusReachable,
            'connectedDevices' => $connectedDevices,
            'maxDevices' => $maxDevices,
            'currentLocation' => $currentLocation,
            'currentRouter' => $currentRouter,
            'isDeviceOnline' => $isDeviceOnline,
            'showDisconnectButton' => $isDeviceOnline,
            'devices' => \App\Models\Device::where('user_id', $user->id)->orderBy('last_seen', 'desc')->get(),
        ]);
    }

    public function forceActivate($subscriptionId)
    {
        $user = Auth::user();

        $subscription = $user->pendingSubscriptions()->find($subscriptionId);
        if (!$subscription) return;

        // Calculate Rollover (in bytes) using User accessor
        $rolloverBytes = $user->getRemainingDataAttribute();
        $plan = $subscription->plan;

        // Convert plan limit to bytes (respecting unit)
        if ($plan->limit_unit === 'Unlimited') {
            $planBytes = null;
        } else {
            $pval = (int) $plan->data_limit;
            if ($pval > 1048576) {
                $planBytes = $pval;
            } else {
                $planBytes = $plan->limit_unit === 'GB' ? (int) ($pval * 1073741824) : (int) ($pval * 1048576);
            }
        }

        $newLimit = is_null($planBytes) ? null : ($planBytes + ($rolloverBytes ?? 0));

        // Activate
        $user->update([
            'plan_id' => $plan->id,
            'data_limit' => $newLimit,
            'data_used' => 0,
            'plan_expiry' => now()->addDays($plan->validity_days),
        ]);

        // Delete the processed subscription
        $subscription->delete();

        // Force Radius Sync
        $user->save();

        session()->flash('success', 'Plan activated! ' . Number::fileSize($rolloverBytes) . ' rolled over.');
    }

    public function redeemVoucher()
    {
        // 1. Validate
        $this->validate([
            'voucherCode' => 'required|string|exists:vouchers,code',
        ]);
    // 2. Find Voucher
    $voucher = \App\Models\Voucher::where('code', $this->voucherCode)->first();
    // 3. Check if Used
    if ($voucher->is_used) {
        $this->addError('voucherCode', 'This voucher has already been used.');
        return;
    }
    $user = \Illuminate\Support\Facades\Auth::user();
    $newPlan = $voucher->plan;
    // 4. THE DECISION: Queue or Activate?
    // SCENARIO A: User has an ACTIVE plan -> Queue It (unless data exhausted)
    if ($user->plan_expiry && $user->plan_expiry > now()) {
        // If current plan has no remaining data -> expire it and activate immediately
        if (($user->remaining_data ?? 0) <= 0) {
            $user->plan_id = null;
            $user->plan_expiry = null;
            $user->save(); // triggers PlanSyncService -> will set radusergroup to default_group
            // fall through to activation
        } else {
            // Add to Queue
            \App\Models\PendingSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $newPlan->id,
            ]);
            session()->flash('success', "Voucher accepted! {$newPlan->name} added to your queue.");
            return;
        }
    }
    // SCENARIO B: User is EXPIRED or NEW -> Activate Immediately
    else {
        // Calculate rollover in bytes using accessor
        $rolloverBytes = $user->getRemainingDataAttribute();

        // Convert new plan limit to bytes (respect unit)
        if ($newPlan->limit_unit === 'Unlimited') {
            $newPlanBytes = null;
        } else {
            $npval = (int) $newPlan->data_limit;
            if ($npval > 1048576) {
                $newPlanBytes = $npval;
            } else {
                $newPlanBytes = $newPlan->limit_unit === 'GB' ? (int) ($npval * 1073741824) : (int) ($npval * 1048576);
            }
        }

        $newLimit = is_null($newPlanBytes) ? null : ($newPlanBytes + ($rolloverBytes ?? 0));

        $user->plan_id = $newPlan->id;
        $user->data_limit = $newLimit;
        $user->data_used = 0;
        $user->plan_expiry = now()->addDays($newPlan->validity_days);
        if ($newPlan->is_family) {
            $user->is_family_admin = true;
            $user->parent_id = null;
            \App\Models\User::where('parent_id', $user->id)->update(['parent_id' => null]);
        } else {
            $user->is_family_admin = false;
            $user->family_limit = null;
        }
        $user->family_limit = $newPlan->family_limit;
        $user->save();

        // Note: The UserObserver will automatically sync this to Radius/MikroTik
        $msg = "Success! {$newPlan->name} is now active.";
        if (($rolloverBytes ?? 0) > 0) {
            $msg .= ' ' . Number::fileSize($rolloverBytes) . ' rolled over.';
        }
        session()->flash('success', $msg);
    }

    // 5. Mark Voucher as Used
    $voucher->update([
        'is_used' => true,
        'used_by' => $user->id,
        'used_at' => now(),
    ]);

    // 6. Create Transaction Record
    try {
        \Illuminate\Support\Facades\Log::info("Attempting to create transaction for voucher {$voucher->code}", [
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
            'amount' => $newPlan->price,
            'reference' => 'VCH-' . $voucher->code,
            'status' => 'success',
            'gateway' => 'voucher',
            'paid_at' => now(),
        ]);

        // Resolve router from session
        $routerId = null;
        $routerIdentity = session('current_router_id');
        if ($routerIdentity) {
            $routerLookup = Schema::hasColumn('routers', 'identity') ? 'identity' : 'nas_identifier';
            $r = \App\Models\Router::where($routerLookup, $routerIdentity)->orWhere('ip_address', $routerIdentity)->first();
            $routerId = $r?->id;
        }

        $transaction = \App\Models\Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
            'amount' => $newPlan->price,
            'reference' => 'VCH-' . $voucher->code,
            'status' => 'success',
            'gateway' => 'voucher',
            'paid_at' => now(),
            'router_id' => $routerId,
        ]);

        \Illuminate\Support\Facades\Log::info("Transaction created successfully for voucher {$voucher->code} with ID: {$transaction->id}");
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error("Failed to create transaction for voucher {$voucher->code}: " . $e->getMessage(), [
            'exception' => $e,
            'user_id' => $user->id,
            'plan_id' => $newPlan->id,
            'plan_exists' => \App\Models\Plan::find($newPlan->id) ? 'yes' : 'no',
            'user_exists' => \App\Models\User::find($user->id) ? 'yes' : 'no',
        ]);
        // Continue execution even if transaction creation fails
    }

    // 7. Reset Form
    $this->reset('voucherCode');
    }

    // Claim / Remember this browser for a device (stores hashed token in Device.meta and sets secure cookie)
    public function claimDevice($deviceId)
    {
        $user = Auth::user();
        $device = Device::where('id', $deviceId)->where('user_id', $user->id)->first();
        if (! $device) {
            Notification::make()->title('Device not found')->danger()->send();
            return;
        }

        try {
            $token = bin2hex(random_bytes(32));
            $hash = Hash::make($token);

            $meta = $device->meta ?? [];
            $meta['browser_token_hash'] = $hash;
            $device->meta = $meta;
            $device->save();

            // Set a secure, HttpOnly cookie for one year
            Cookie::queue(Cookie::make('fastlink_device_token', $token, 60 * 24 * 365, '/', null, true, true, false, 'Lax'));

            session(['current_device_mac' => $device->mac]);

            Notification::make()->title('Device remembered')->success()->send();
        } catch (\Exception $e) {
            Log::error('Failed to claim device: ' . $e->getMessage());
            Notification::make()->title('Error')->danger()->send();
        }
    }

    public function forgetDevice($deviceId)
    {
        $user = Auth::user();
        $device = Device::where('id', $deviceId)->where('user_id', $user->id)->first();
        if (! $device) return;

        $meta = $device->meta ?? [];
        unset($meta['browser_token_hash']);
        $device->meta = $meta;
        $device->save();

        Cookie::queue(Cookie::forget('fastlink_device_token'));
        if (session('current_device_mac') === $device->mac) session()->forget('current_device_mac');

        Notification::make()->title('Device forgotten')->success()->send();
    }

    /**
     * Disconnect user from MikroTik router via HTTP API
     * 
     * This method attempts to remove active hotspot session from MikroTik
     * Requires MikroTik API credentials to be configured in .env
     */
    
    /**
     * Disconnect user from MikroTik router via HTTP API
     * 
     * This method attempts to remove active hotspot session from MikroTik
     * Requires MikroTik API credentials to be configured in .env
     */
    protected function disconnectFromMikroTik($username)
    {
        // Get MikroTik API credentials from config
        $apiHost = config('services.mikrotik.api_host', env('MIKROTIK_API_HOST'));
        $apiUser = config('services.mikrotik.api_user', env('MIKROTIK_API_USER'));
        $apiPassword = config('services.mikrotik.api_password', env('MIKROTIK_API_PASSWORD'));
        
        // Skip if API not configured
        if (!$apiHost || !$apiUser || !$apiPassword) {
            Log::info("MikroTik API not configured - skipping automatic disconnect for {$username}");
            return;
        }
        
        // Use RouterOS API to disconnect user
        // Format: http://router-ip/rest/ip/hotspot/active/remove?numbers=<id>
        // First, find the active session ID for this user
        
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 5,
                'verify' => false, // Disable SSL verification for local routers
            ]);
            
            // Get active sessions
            $response = $client->get("http://{$apiHost}/rest/ip/hotspot/active", [
                'auth' => [$apiUser, $apiPassword],
            ]);
            
            $sessions = json_decode($response->getBody(), true);
            
            // Find session(s) for this username
            foreach ($sessions as $session) {
                if (isset($session['user']) && $session['user'] === $username) {
                    $sessionId = $session['.id'];
                    
                    // Remove the session
                    $client->delete("http://{$apiHost}/rest/ip/hotspot/active/{$sessionId}", [
                        'auth' => [$apiUser, $apiPassword],
                    ]);
                    
                    Log::info("Successfully disconnected user {$username} from MikroTik (session ID: {$sessionId})");
                }
            }
        } catch (\Exception $e) {
            Log::error("MikroTik API error: " . $e->getMessage());
            throw $e;
        }
    }
}