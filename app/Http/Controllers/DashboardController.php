<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\RadCheck;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
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
        ]);
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
        $validSubscription = null;
        if (class_exists(\App\Models\Subscription::class)) {
            $validSubscription = \App\Models\Subscription::where('user_id', $user->id)
                ->where('status', 'ACTIVE')
                ->where('expires_at', '>', now())
                ->where(function ($q) {
                    $q->where('data_remaining', '>', 0)->orWhereNull('data_limit');
                })
                ->orderBy('expires_at', 'desc')
                ->first();
        } else {
            $hasExpiry = $user->plan_expiry && $user->plan_expiry->isFuture();
            $dataRemaining = is_null($user->data_limit) ? null : max(0, ($user->data_limit ?? 0) - ($user->data_used ?? 0));

            if ($hasExpiry && (is_null($user->data_limit) || $dataRemaining > 0)) {
                $validSubscription = (object) ['plan_id' => $user->plan_id, 'expires_at' => $user->plan_expiry];
            }
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

        $gateway = config('services.mikrotik.gateway') ?? env('MIKROTIK_LOGIN_URL') ?? 'http://10.5.50.1/login';
        $loginUrl = (strpos($gateway, '://') === false ? 'http://' . $gateway : $gateway);
        if (! preg_match('#/login#', $loginUrl)) {
            $loginUrl = rtrim($loginUrl, '/') . '/login';
        }

        $params = http_build_query([
            'username' => $user->username,
            'password' => $password,
            'dst' => route('dashboard'),
        ]);

        $redirectUrl = $loginUrl . '?' . $params;

        return response()->json(['redirect_url' => $redirectUrl]);
    }
}