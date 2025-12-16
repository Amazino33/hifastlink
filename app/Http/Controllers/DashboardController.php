<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
}