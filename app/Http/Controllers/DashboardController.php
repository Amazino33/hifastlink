<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\RadCheck;
use App\Models\RadAcct;
use App\Models\Router;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // 1) MAC capture: read ?mac=... and store in session; else reuse existing; else null
        if ($request->filled('mac')) {
            session(['current_device_mac' => $request->input('mac')]);
        }
        $currentMac = session('current_device_mac');

        // 1b) Router identity capture: read ?router=... and store in session
        if ($request->has('router')) {
            session(['current_router' => $request->input('router')]);
        }

        // Use stored router identity (from session) to find router location
        $currentRouterNasIdentifier = session('current_router');
        $currentRouter = $currentRouterNasIdentifier
            ? Router::where('nas_identifier', $currentRouterNasIdentifier)->first()
            : null;
        $currentLocation = $currentRouter
            ? ($currentRouter->location ?: ($currentRouter->name ?: 'Unknown Location'))
            : 'Unknown Location';

        // 2) Device status: check active RADIUS session for this MAC
        $activeSessionsQuery = RadAcct::where('username', $user->username)
            ->whereNull('acctstoptime');

        $activeSessionCount = (clone $activeSessionsQuery)->count();

        $isCurrentDeviceConnected = false;
        $activeSession = null;
        $routerLocation = null;
        
        if ($currentMac) {
            $activeSession = (clone $activeSessionsQuery)
                ->where('callingstationid', $currentMac)
                ->orderByDesc('acctstarttime')
                ->first();
            
            $isCurrentDeviceConnected = (bool) $activeSession;
            
            // Fetch router location if session exists
            if ($activeSession) {
                $router = null;
                
                // Try to match by NAS identifier first (most reliable)
                if (!empty($activeSession->nasidentifier)) {
                    $router = Router::where($routerLookupColumn, $activeSession->nasidentifier)
                        ->where('is_active', true)
                        ->first();
                }
                
                // Fall back to IP address matching
                if (!$router && !empty($activeSession->nasipaddress)) {
                    $router = Router::where('ip_address', $activeSession->nasipaddress)
                        ->where('is_active', true)
                        ->first();
                }
                
                // Build location string
                if ($router) {
                    $routerLocation = $router->name;
                    if (!empty($router->location)) {
                        $routerLocation .= ' - ' . $router->location;
                    }
                } else {
                    // Fallback to IP if router not found in DB
                    $routerLocation = 'Router: ' . ($activeSession->nasipaddress ?? 'Unknown');
                }
            }
        }
        
        // Get real-time session data from cache
        $sessionData = Cache::get("user_session:{$user->phone}");
        
        // If no recent data in cache, user is offline
        if (!$sessionData) {
            $sessionData = [
                'data_used' => $user->data_used ?? 0, // Get from DB for monthly total
                'current_speed' => 0,
                'connection_status' => 'inactive',
                'ip_address' => null,
                'uptime' => '00:00:00',
                'remaining_bytes' => $user->data_limit ?? 0,
                'limit_bytes' => $user->data_limit ?? 0,
                'last_updated' => null
            ];
        }
        
        // Calculate subscription info
        $subscriptionDaysRemaining = $this->getSubscriptionDaysRemaining($user);
        $subscriptionStatus = $subscriptionDaysRemaining > 0 ? 'active' : 'expired';
        
        // Format data for display
        $dataUsedFormatted = $this->formatBytes($sessionData['data_used']);
        $dataLimitFormatted = $sessionData['limit_bytes'] > 0 
            ? $this->formatBytes($sessionData['limit_bytes']) 
            : 'Unlimited';
        
        // Calculate percentage for progress bar
        $dataUsagePercentage = 0;
        if ($sessionData['limit_bytes'] > 0) {
            $dataUsagePercentage = ($sessionData['data_used'] / $sessionData['limit_bytes']) * 100;
            $dataUsagePercentage = min($dataUsagePercentage, 100); // Cap at 100%
        }

        $allRouters = Router::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('dashboard', [
            // Subscription Info
            'subscriptionDays' => $subscriptionDaysRemaining,
            'subscriptionStatus' => $subscriptionStatus,
            'subscriptionValidUntil' => $user->subscription_end_date 
                ? $user->subscription_end_date->format('d M Y') 
                : 'N/A',
            
            // Data Usage
            'dataUsed' => $dataUsedFormatted,
            'dataLimit' => $dataLimitFormatted,
            'dataUsagePercentage' => round($dataUsagePercentage, 1),
            
            // Connection Stats
            'connectionStatus' => $sessionData['connection_status'],
            'currentSpeed' => $sessionData['current_speed'] . ' Mbps',
            'currentIp' => $sessionData['ip_address'] ?? 'N/A',
            'uptime' => $sessionData['uptime'],
            
            // Additional Info
            'lastUpdated' => $sessionData['last_updated'] ?? null,

            // Device awareness
            'currentDeviceMac' => $currentMac,
            'isCurrentDeviceConnected' => $isCurrentDeviceConnected,
            'activeSessionCount' => $activeSessionCount,
            'connectUrl' => $this->routerLoginUrl(),
            'routerLocation' => $routerLocation,
            'currentLocation' => $currentLocation,
            'allRouters' => $allRouters,
        ]);
    }

    private function routerLoginUrl(): string
    {
        $gateway = config('services.mikrotik.gateway') ?? env('MIKROTIK_GATEWAY') ?? 'http://login.wifi/login';
        $loginUrl = (strpos($gateway, '://') === false ? 'http://' . $gateway : $gateway);
        if (! preg_match('#/login#', $loginUrl)) {
            $loginUrl = rtrim($loginUrl, '/') . '/login';
        }

        return $loginUrl;
    }
    
    private function getSubscriptionDaysRemaining($user)
    {
        if (!$user->subscription_end_date) {
            return 0;
        }
        
        $now = now();
        $endDate = $user->subscription_end_date;
        
        if ($endDate->isPast()) {
            return 0;
        }
        
        return $now->diffInDays($endDate);
    }
    
    private function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
    
    // Optional: API endpoint to get real-time data for AJAX updates
    public function getRealtimeData()
    {
        $user = Auth::user();
        $sessionData = Cache::get("user_session:{$user->phone}");
        
        if (!$sessionData) {
            return response()->json([
                'status' => 'offline',
                'connection_status' => 'inactive',
                'current_speed' => 0,
                'data_used' => $this->formatBytes($user->data_used ?? 0),
            ]);
        }
        
        return response()->json([
            'status' => 'online',
            'connection_status' => $sessionData['connection_status'],
            'current_speed' => $sessionData['current_speed'] . ' Mbps',
            'data_used' => $this->formatBytes($sessionData['data_used']),
            'uptime' => $sessionData['uptime'],
            'ip_address' => $sessionData['ip_address'],
            'last_updated' => $sessionData['last_updated'],
        ]);
    }

    /**
     * Build a GET-based redirect URL to the router and return it to the client.
     * The browser will navigate to this URL to perform captive portal login and return to dashboard.
     */
    public function connectToRouter(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Determine if user has an active subscription using Subscription model when available
        // Validate active plan (used instead of Subscription model)
        $validSubscription = null;
        $hasExpiry = $user->plan_expiry && $user->plan_expiry->isFuture();
        $dataRemaining = is_null($user->data_limit) ? null : max(0, ($user->data_limit ?? 0) - ($user->data_used ?? 0));

        if ($hasExpiry && (is_null($user->data_limit) || $dataRemaining > 0)) {
            $validSubscription = (object) ['plan_id' => $user->plan_id, 'expires_at' => $user->plan_expiry];
        }

        if (! $validSubscription) {
            return response()->json(['message' => 'No active subscription. Please renew to connect.'], 422);
        }

        // Self-repair plan_id if missing
        if (isset($validSubscription->plan_id) && empty($user->plan_id) && $validSubscription->plan_id) {
            try {
                $user->plan_id = $validSubscription->plan_id;
                $user->save();
                Log::info('Repaired missing plan_id for user '.$user->id.' with subscription.');
            } catch (\Exception $e) {
                Log::warning('Failed to repair plan_id for user '.$user->id.': '.$e->getMessage());
            }
        }

        // Fetch password from radcheck if present
        $rad = RadCheck::where('username', $user->username)->where('attribute', 'Cleartext-Password')->first();
        $password = $rad ? $rad->value : ($user->radius_password ?? null);

        if (! $password) {
            Log::warning("No cleartext password found for user {$user->username}");
            return response()->json(['message' => 'Missing credentials. Please contact support.'], 500);
        }

        // Use login.wifi (DNS name) instead of IP address to avoid MikroTik redirect loops
        $gateway = config('services.mikrotik.gateway') ?? env('MIKROTIK_GATEWAY') ?? 'http://login.wifi/login';
        $loginUrl = (strpos($gateway, '://') === false ? 'http://' . $gateway : $gateway);
        if (! preg_match('#/login#', $loginUrl)) {
            $loginUrl = rtrim($loginUrl, '/') . '/login';
        }

        // Build URL with top-level parameters (MikroTik format)
        $redirectUrl = $loginUrl 
            . '?username=' . urlencode($user->username)
            . '&password=' . urlencode($password)
            . '&dst=' . urlencode("http://login.wifi/redirect.html");

        return response()->json(['redirect_url' => $redirectUrl]);
    }
}