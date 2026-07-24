<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;
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

        // Auto-connect when arriving from captive portal redirect (MikroTik passes ?mac= in the URL).
        // Skip if we just completed a bridge (cooldown prevents RADIUS timing race → redirect loop).
        if (request()->filled('mac') && ! session()->pull('bridge_completed')) {
            $mac = request()->input('mac');
            $routerIdentifier = $this->router
                ?? session('current_router')
                ?? session('hotspot_router');

            if ($routerIdentifier) {
                $alreadyOnline = RadAcct::where('username', $user->username)
                    ->where('callingstationid', $mac)
                    ->whereNull('acctstoptime')
                    ->exists();

                $canConnect = (new \App\Services\SubscriptionService())->canConnectToHotspot($user);

                if (!$alreadyOnline && $canConnect) {
                    return redirect()->route('connect.bridge', ['router' => $routerIdentifier]);
                }
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
        }
        $user->family_limit = $plan->family_limit ?? 0;
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
            $devices = Device::where('user_id', $user->id)->latest('last_seen')->paginate(5, ['*'], 'devices_page');
            $activeSession_ = RadAcct::forUser($user->username)
                ->whereNull('acctstoptime')
                ->get()
                ->keyBy(function ($session) {
                    return preg_replace('/[^a-f0-9]/', '', strtolower($session->callingstationid)); // MAC address as key
                });
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

        // MikroTik: acctoutputoctets = bytes sent to client (download), acctinputoctets = bytes from client (upload)
        $sessionDownload = $activeSession ? Number::fileSize((int)($activeSession->acctoutputoctets ?? 0)) : null;
        $sessionUpload   = $activeSession ? Number::fileSize((int)($activeSession->acctinputoctets ?? 0)) : null;

        // Determine start date for counting usage (plan start). If missing, fallback to 1 year back to avoid huge scans
        $startDate = $user->plan_started_at ?? now()->subYears(1);

        // Identify the family master ID (parent if exists, else self)
        $masterId = $user->parent_id ?? $user->id;

        // Get the master user with plan loaded
        $masterUser = User::with('plan')->find($masterId);

        // Get all family usernames (master + children)
        $familyUsernames = User::where('id', $masterId)->orWhere('parent_id', $masterId)->pluck('username');

        // Voucher users connect with the voucher code as their RADIUS username — include them
        $voucherUsernames = \App\Models\Voucher::where('created_by', $masterId)->pluck('code');

        $allUsernames = $familyUsernames->merge($voucherUsernames);

        // Sum all historical sessions for the family + voucher users since the plan started
        $historyUsage = RadAcct::whereIn('username', $allUsernames)
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

        $isAdminUser = $user->isAdmin();
        $isFreePass  = $user->isFreePass();
        $hasUnrestricted = $user->hasUnrestrictedAccess();

        // Unrestricted roles are always 'active' regardless of plan
        $hasVoucherAccess = ! $masterUser->plan_id
            && $masterUser->plan_expiry
            && $masterUser->plan_expiry->isFuture();

        if ($hasUnrestricted) {
            $subscriptionStatus = 'active';
        } elseif ($hasVoucherAccess) {
            $subscriptionStatus = 'active';
        } elseif (!$masterUser->plan_id) {
            $subscriptionStatus = 'inactive';
        } else {
            $subscriptionStatus = $masterUser->current_plan_status ?? 'inactive';
        }

        $connectionStatus = ($activeSession) ? 'active' : 'offline';

        if (!$radiusReachable) {
            $connectionStatus = 'unknown';
        }

        // Force offline when there is no plan — unrestricted roles and voucher access bypass this
        if (!$masterUser->plan_id && !$hasUnrestricted && !$hasVoucherAccess) {
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

        // Admin override: no expiry concern, no device cap, purple badge + live network stats
        $networkStats = null;
        if ($isAdminUser) {
            $subscriptionDays = null;
            $daysBadgeClass   = 'bg-purple-600 text-white';
            $maxDevices       = PHP_INT_MAX;

            $radacctExists = \Illuminate\Support\Facades\Schema::hasTable('radacct');

            $todayBytes = $radacctExists
                ? (int) (\App\Models\RadAcct::whereDate('acctstarttime', today())
                    ->selectRaw('COALESCE(SUM(acctinputoctets + acctoutputoctets), 0) as total')
                    ->value('total') ?? 0)
                : 0;

            $networkStats = [
                'active_sessions'   => $radacctExists ? \App\Models\RadAcct::whereNull('acctstoptime')->count() : 0,
                'users_online'      => $radacctExists ? \App\Models\RadAcct::whereNull('acctstoptime')->distinct('username')->count('username') : 0,
                'total_users'       => \App\Models\User::count(),
                'active_vouchers'   => \App\Models\Voucher::where('is_used', false)->count(),
                'today_bytes_human' => $radacctExists ? Number::fileSize($todayBytes) : '—',
            ];
        }

        // Staff / free-pass override: no expiry concern, 2-device cap
        if ($isFreePass) {
            $subscriptionDays = null;
            $daysBadgeClass   = 'bg-blue-600 text-white';
            $maxDevices       = 2;
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

        // Uptime: acctsessiontime from radacct is a duration in seconds (e.g. 2940).
        // Convert numeric seconds into a human duration; otherwise fall back to acctstarttime or user's last_online.
        if ($activeSession) {
            if (is_numeric($activeSession->acctsessiontime) && (int) $activeSession->acctsessiontime > 0) {
                $uptime = \Carbon\CarbonInterval::seconds((int) $activeSession->acctsessiontime)->cascade()->forHumans();
            } elseif (!empty($activeSession->acctstarttime)) {
                $uptime = Carbon::parse($activeSession->acctstarttime)->diffForHumans();
            } else {
                $uptime = '-';
            }
        } else {
            $uptime = $user->last_online ? $user->last_online->diffForHumans() : '-';
        }

        // Fetch all transactions
        $recentTransactions = \App\Models\Transaction::where('user_id', Auth::id())
            ->with('plan')
            ->latest()
            ->paginate(10);

        // Voucher status panel — paginated, only built if this user has created vouchers
        $userVouchers = \App\Models\Voucher::where('created_by', $user->id)
            ->with('plan')
            ->orderByDesc('created_at')
            ->paginate(5, ['*'], 'vouchers_page');

        $myVouchers = $userVouchers; // paginator (empty or populated)

        if ($userVouchers->isNotEmpty()) {
            $codes = $userVouchers->pluck('code');

            $activeVoucherSessions = RadAcct::whereIn('username', $codes)
                ->whereNull('acctstoptime')
                ->select(['username', 'framedipaddress', 'callingstationid', 'acctsessiontime', 'acctstarttime', 'acctinputoctets', 'acctoutputoctets'])
                ->get()
                ->groupBy('username');

            $dataUsed = RadAcct::whereIn('username', $codes)
                ->select('username', DB::raw('SUM(COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0)) as total'))
                ->groupBy('username')
                ->pluck('total', 'username');

            $myVouchers = $userVouchers->through(function ($v) use ($activeVoucherSessions, $dataUsed, $user) {
                $bytes     = (int) ($dataUsed->get($v->code, 0));
                $exhausted = $v->used_count >= $v->max_uses;
                // Creator-based vouchers: expired when creator's plan expires
                // But only if the voucher itself isn't already fully used (that's 'exhausted')
                if ($exhausted) {
                    $expired = false;
                } elseif ($v->created_by && $v->created_by === $user->id) {
                    // Viewing own vouchers — check own plan directly (already loaded)
                    $expired = $user->plan_expiry && $user->plan_expiry->isPast();
                } elseif ($v->created_by) {
                    $vCreator = $v->creator;
                    $expired = $vCreator && $vCreator->plan_expiry && $vCreator->plan_expiry->isPast();
                } else {
                    $expired = $v->expires_at && $v->expires_at->isPast();
                }
                $vSessions = $activeVoucherSessions->get($v->code, collect())->map(function ($s) {
                    $sBytes = (int)($s->acctinputoctets ?? 0) + (int)($s->acctoutputoctets ?? 0);
                    $secs   = is_numeric($s->acctsessiontime) ? (int)$s->acctsessiontime : 0;
                    $dur    = $secs > 0
                        ? \Carbon\CarbonInterval::seconds($secs)->cascade()->forHumans()
                        : ($s->acctstarttime ? \Carbon\Carbon::parse($s->acctstarttime)->diffForHumans() : '—');
                    return [
                        'ip'       => $s->framedipaddress ?? '—',
                        'mac'      => $s->callingstationid ? strtoupper($s->callingstationid) : '—',
                        'duration' => $dur,
                        'data'     => Number::fileSize($sBytes),
                    ];
                })->values()->toArray();
                $online = count($vSessions);

                return [
                    'code'      => $v->code,
                    'plan'      => $v->plan?->name ?? '—',
                    'used'      => $v->used_count,
                    'max'       => $v->max_uses,
                    'online'    => $online,
                    'sessions'  => $vSessions,
                    'data_used' => Number::fileSize($bytes),
                    'expires_at'=> $v->expires_at?->format('M d, Y') ?? 'No expiry',
                    'status'    => $expired ? 'expired' : ($exhausted ? 'exhausted' : ($online > 0 ? 'active' : 'idle')),
                ];
            });
        }

        $sessionHistory = RadAcct::whereIn('username', $allUsernames)
            ->whereNotNull('acctstoptime')
            ->orderByDesc('acctstoptime')
            ->paginate(5, ['*'], 'sessions_page');

        return view('livewire.user-dashboard', [
            'user' => $user,
            'plans' => $plans,
            'totalUsed' => $totalUsed,
            'formattedTotalUsed' => $formattedTotalUsed,
            'formattedDataLimit' => (isset($effectivePlanBytes) && $effectivePlanBytes !== null)
                ? Number::fileSize($effectivePlanBytes)   // plan bytes + rollover (computed above)
                : 'Unlimited',
            'hasRollover' => isset($masterUser) && ($masterUser->rollover_available_bytes ?? 0) > 0 && ($masterUser->rollover_validity_days ?? 0) > 0,
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
            'devices' => $devices ?? [],
            'activeSession_' => $activeSession_ ?? collect(),
            'myVouchers'      => $myVouchers,
            'sessionDownload' => $sessionDownload,
            'sessionUpload'   => $sessionUpload,
            'sessionHistory'  => $sessionHistory,
            'isAdminUser'      => $isAdminUser,
            'isFreePass'       => $isFreePass,
            'hasUnrestricted'  => $hasUnrestricted,
            'hasVoucherAccess' => $hasVoucherAccess,
            'networkStats'     => $networkStats,
            'ownedRouter'      => $this->getOwnedRouterData($user),
        ]);
    }

    private function getOwnedRouterData($user): ?array
    {
        $router = \App\Models\Router::where('owner_id', $user->id)->first();

        if (! $router) {
            return null;
        }

        $activeSessions = $router->activeSessions()
            ->select('username', 'framedipaddress', 'callingstationid', 'acctstarttime', 'acctsessiontime',
                DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0) as total_bytes'))
            ->limit(20)
            ->get();

        $subscribers = \App\Models\User::where('router_id', $router->id)
            ->whereNotNull('plan_id')
            ->with('plan')
            ->latest('plan_started_at')
            ->limit(20)
            ->get()
            ->map(fn ($u) => [
                'name'       => $u->display_name,
                'phone'      => $u->phone,
                'plan'       => $u->plan?->name ?? 'Custom',
                'expiry'     => $u->plan_expiry?->format('d M Y') ?? '—',
                'data_used'  => $u->formatted_data_used,
                'status'     => $u->plan_expiry && $u->plan_expiry->isFuture() ? 'active' : 'expired',
            ]);

        $todayBytes = $router->sessions()
            ->whereDate('acctstarttime', today())
            ->sum(DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)'));

        $monthBytes = $router->sessions()
            ->where('acctstarttime', '>=', now()->startOfMonth())
            ->sum(DB::raw('COALESCE(acctinputoctets, 0) + COALESCE(acctoutputoctets, 0)'));

        return [
            'router'          => $router,
            'is_online'       => $router->is_online,
            'active_users'    => $activeSessions->pluck('username')->unique()->count(),
            'active_sessions' => $activeSessions,
            'subscribers'     => $subscribers,
            'total_subscribers' => $subscribers->count(),
            'today_bytes'     => Number::fileSize($todayBytes),
            'month_bytes'     => Number::fileSize($monthBytes),
        ];
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
        $update = [
            'plan_id' => $plan->id,
            'data_limit' => $newLimit,
            'data_used' => 0,
            'plan_expiry' => now()->addDays($plan->validity_days),
            'family_limit' => $plan->family_limit,
        ];

        if ($plan->is_family) {
            $update['is_family_admin'] = true;
            $update['parent_id'] = null;
        } else {
            $update['is_family_admin'] = false;
            $update['family_limit'] = null;
        }

        $user->update($update);

        // If the plan is a family plan, unlink any users that were previously marked as this user's children
        if ($plan->is_family) {
            \App\Models\User::where('parent_id', $user->id)->update(['parent_id' => null]);
        }

        // Delete the processed subscription
        $subscription->delete();

        // Force Radius Sync (observer will handle most sync tasks)
        $user->save();

        session()->flash('success', 'Plan activated! ' . Number::fileSize($rolloverBytes) . ' rolled over.');
    }

    public function redeemVoucher(): mixed
    {
        $this->voucherCode = strtoupper(trim($this->voucherCode));

        $this->validate(['voucherCode' => 'required|string|exists:vouchers,code']);

        $voucher = \App\Models\Voucher::where('code', $this->voucherCode)->first();
        $user    = Auth::user();

        try {
            return $this->processVoucherRedemption($voucher, $user);
        } catch (\Throwable $e) {
            Log::error('redeemVoucher: unexpected error', [
                'user_id' => $user?->id,
                'voucher' => $this->voucherCode,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->addError('voucherCode', 'An error occurred while activating your voucher. Please try again or contact support.');
            return null;
        }
    }

    private function processVoucherRedemption(\App\Models\Voucher $voucher, $user): mixed
    {
        // Creator-based vouchers: check creator's plan is still active
        $creator = $voucher->creator;
        if ($creator) {
            $subscriptionService = new \App\Services\SubscriptionService();
            if (! $subscriptionService->canConnectToHotspot($creator)) {
                $this->addError('voucherCode', "The voucher owner's plan has expired or run out of data.");
                return null;
            }
        }

        // Link user under the voucher creator if they're a family head (not admin)
        if ($creator && ! $creator->isAdmin() && ! $user->parent_id && $creator->id !== $user->id) {
            $user->updateQuietly(['parent_id' => $creator->id]);
        }

        // Atomically claim one slot with all checks inside the transaction
        $claimResult = DB::transaction(function () use ($voucher, $user) {
            $fresh = \App\Models\Voucher::lockForUpdate()->find($voucher->id);

            if ($fresh->used_count >= $fresh->max_uses) {
                return 'limit_reached';
            }

            if (\App\Models\Transaction::where('reference', 'VCH-' . $fresh->code)
                ->where('user_id', $user->id)->exists()) {
                return 'already_redeemed';
            }

            $newCount = $fresh->used_count + 1;
            $fresh->update([
                'used_count' => $newCount,
                'is_used'    => $newCount >= $fresh->max_uses,
                'used_by'    => $user->id,
                'used_at'    => now(),
            ]);

            if (is_null($fresh->expires_at) && $fresh->duration_hours) {
                $fresh->update(['expires_at' => now()->addHours($fresh->duration_hours)]);
            }

            return true;
        });

        if ($claimResult === 'limit_reached') {
            $this->addError('voucherCode', 'This voucher has reached its usage limit.');
            return null;
        }
        if ($claimResult === 'already_redeemed') {
            Notification::make()->title('Already Redeemed')->body('You have already redeemed this voucher.')->warning()->send();
            return null;
        }
        if ($claimResult !== true) {
            $this->addError('voucherCode', 'This voucher was just used up. Please try another.');
            return null;
        }

        $newPlan = $voucher->plan;

        // ── Custom voucher with no attached plan ──────────────────────────────
        // Activates temporary access directly from the voucher's own parameters.
        if (! $newPlan) {
            if (! $voucher->duration_hours) {
                $this->addError('voucherCode', 'This voucher has no valid access configuration.');
                return null;
            }

            $user->plan_id         = null;
            $user->data_limit      = $voucher->is_unlimited
                ? null
                : ($voucher->data_limit_mb ? $voucher->data_limit_mb * 1048576 : null);
            $user->data_used       = 0;
            $user->plan_expiry     = now()->addHours($voucher->duration_hours);
            $user->plan_started_at = now();
            $user->connection_status = 'active';
            $user->save();

            // Explicit sync needed when user had no plan (plan_id didn't change, so observer skips it).
            // For users who had a plan, the observer already synced — this is a harmless second pass.
            try {
                \App\Services\PlanSyncService::syncUserPlan($user);

                if ($voucher->speed_limit_upload || $voucher->speed_limit_download) {
                    \App\Models\RadReply::updateOrCreate(
                        ['username' => $user->username, 'attribute' => 'Mikrotik-Rate-Limit'],
                        ['op' => ':=', 'value' => ($voucher->speed_limit_upload ?? 0) . 'k/' . ($voucher->speed_limit_download ?? 0) . 'k']
                    );
                }
            } catch (\Throwable $e) {
                Log::error('redeemVoucher: RADIUS sync failed', [
                    'user_id' => $user->id,
                    'voucher' => $voucher->code,
                    'error'   => $e->getMessage(),
                ]);
            }

            $this->recordVoucherTransaction($user, null, $voucher);

            $days = $voucher->duration_hours / 24;
            $msg  = round($days, 1) . ' ' . (round($days) === 1.0 ? 'day' : 'days') . ' of access activated!';
            if ($voucher->is_unlimited) {
                $msg .= ' Unlimited data.';
            } elseif ($voucher->data_limit_mb) {
                $msg .= ' ' . Number::fileSize($voucher->data_limit_mb * 1048576) . ' data.';
            }

            $router = $user->router_id ? \App\Models\Router::find($user->router_id) : null;
            if ($router) {
                Notification::make()->title('Voucher Activated!')->body($msg)->success()->send();
                return redirect()->route('connect.bridge', ['router' => $router->nas_identifier]);
            }

            Notification::make()->title('Voucher Activated!')->body($msg)->success()->send();
            return null;
        }

        // Active plan with remaining data → queue it
        $hasActiveData = $user->plan_expiry
            && $user->plan_expiry > now()
            && ($user->remaining_data ?? 1) > 0;

        if ($hasActiveData) {
            \App\Models\PendingSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $newPlan->id,
            ]);
            $this->recordVoucherTransaction($user, $newPlan, $voucher);
            Notification::make()->title('Plan Queued')->body("{$newPlan->name} will activate automatically when your current plan runs out.")->success()->send();
            return null;
        }

        // Activate immediately (expired, exhausted, or no plan)
        $rolloverBytes = $user->getRemainingDataAttribute();

        if ($newPlan->limit_unit === 'Unlimited') {
            $newPlanBytes = null;
        } else {
            $npval = (int) $newPlan->data_limit;
            $newPlanBytes = $npval > 1048576
                ? $npval
                : ($newPlan->limit_unit === 'GB' ? (int) ($npval * 1073741824) : (int) ($npval * 1048576));
        }
        $newLimit = is_null($newPlanBytes) ? null : ($newPlanBytes + ($rolloverBytes ?? 0));

        $user->plan_id         = $newPlan->id;
        $user->data_limit      = $newLimit;
        $user->data_used       = 0;
        $user->plan_expiry     = now()->addDays($newPlan->validity_days);
        $user->plan_started_at = now();
        if ($newPlan->is_family) {
            $user->is_family_admin = true;
            $user->parent_id       = null;
            \App\Models\User::where('parent_id', $user->id)->update(['parent_id' => null]);
        } else {
            $user->is_family_admin = false;
        }
        $user->family_limit = $newPlan->family_limit ?? 0;
        $user->save(); // UserObserver → PlanSyncService → RADIUS sync

        $this->recordVoucherTransaction($user, $newPlan, $voucher);

        $msg = "{$newPlan->name} activated!";
        if (($rolloverBytes ?? 0) > 0) {
            $msg .= ' ' . Number::fileSize($rolloverBytes) . ' rolled over from your previous plan.';
        }

        // If the user has a known router, redirect to connect.bridge so they get online immediately
        $router = $user->router_id ? \App\Models\Router::find($user->router_id) : null;
        if ($router) {
            Notification::make()->title('Plan Activated!')->body($msg)->success()->send();
            return redirect()->route('connect.bridge', ['router' => $router->nas_identifier]);
        }

        Notification::make()->title('Plan Activated!')->body($msg)->success()->send();
        return null;
    }

    private function recordVoucherTransaction(User $user, ?\App\Models\Plan $plan, \App\Models\Voucher $voucher): void
    {
        try {
            \App\Models\Transaction::create([
                'user_id'  => $user->id,
                'plan_id'  => $plan?->id,
                'amount'   => $plan?->price ?? 0,
                'reference'=> 'VCH-' . $voucher->code,
                'status'   => 'success',
                'gateway'  => 'voucher',
                'paid_at'  => now(),
                'router_id'=> $user->router_id ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error("Voucher transaction failed for {$voucher->code}: " . $e->getMessage());
        }
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

    public function disconnectDevice(int $deviceId): void
    {
        $user = Auth::user();
        $device = Device::where('id', $deviceId)->where('user_id', $user->id)->first();

        if (! $device) {
            Notification::make()->title('Device not found')->danger()->send();
            return;
        }

        $mac = strtoupper(str_replace(['-', '.'], ':', $device->mac));

        // Close only this device's RADIUS session
        DB::table('radacct')
            ->whereRaw('LOWER(username) = ?', [strtolower($user->username)])
            ->whereRaw("UPPER(REPLACE(REPLACE(callingstationid, '-', ':'), '.', ':')) = ?", [$mac])
            ->whereNull('acctstoptime')
            ->update([
                'acctstoptime'       => now(),
                'acctterminatecause' => 'User-Request',
            ]);

        $device->update(['is_connected' => false, 'last_seen' => now()]);

        // Remove the live session from the router via REST API
        try {
            $this->disconnectFromMikroTik($user->username);
        } catch (\Throwable $e) {
            Log::warning('disconnectDevice: router disconnect failed', ['error' => $e->getMessage()]);
        }

        // If disconnecting the current device, clear its session markers
        if (session('current_device_mac') === $device->mac) {
            session()->forget('current_device_mac');
            Cookie::queue(Cookie::forget('fastlink_device_token'));
        }

        Notification::make()->title('Device disconnected')->success()->send();
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