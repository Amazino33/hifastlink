<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Carbon\Carbon;

class DataImportController extends Controller
{
    public function import(Request $request)
    {
        try {
            // Get API key from request (supports both body and header)
            $apiKey = $request->input('api_key') ?? $request->header('X-API-KEY');
            
            // New format doesn't require API key (uses Cloudflare Worker)
            // If API key is provided, verify it
            if ($apiKey && $apiKey !== config('app.import_api_key')) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            // Detect format and normalize data
            if ($request->has('router') && $request->has('users')) {
                // NEW FORMAT: { "router": "name", "users": [...] }
                $routerData = $this->normalizeNewFormat($request->all());
            } elseif ($request->has('router_name') && $request->has('online_users')) {
                // OLD MIKROTIK FORMAT: flat structure with online_users
                $routerData = [$request->all()];
            } else {
                // POWERSHELL FORMAT: wrapped in data array
                $validated = $request->validate([
                    'data' => 'required'
                ]);
                
                $routerData = is_string($validated['data']) 
                    ? json_decode($validated['data'], true) 
                    : $validated['data'];
            }
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error' => 'Invalid JSON format',
                    'details' => json_last_error_msg()
                ], 400);
            }
            
            // Store in cache instead of database (expires in 10 minutes)
            $processed = $this->cacheRouterData($routerData);
            
            Log::info('Router data cached', [
                'users_cached' => $processed['users_cached'],
                'routers' => $processed['routers']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Router data processed successfully',
                'summary' => [
                    'users_processed' => $processed['users_cached'],
                    'routers_processed' => count($processed['routers']),
                    'cached_until' => now()->addMinutes(10)->format('Y-m-d H:i:s')
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Import failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Import failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    private function cacheRouterData($data)
    {
        $usersCached = 0;
        $routers = [];
        
        foreach ($data as $routerInfo) {
            $routerName = $routerInfo['router_name'] ?? 'Unknown';
            $timestamp = $routerInfo['timestamp'] ?? now();
            
            $routers[] = $routerName;
            
            // Parse online users
            $onlineUsers = $routerInfo['online_users'] ?? [];
            
            if (is_string($onlineUsers)) {
                $onlineUsers = json_decode($onlineUsers, true) ?? [];
            }
            
            foreach ($onlineUsers as $sessionData) {
                $username = $sessionData['user'] ?? null;
                
                if (!$username) continue;
                
                // Calculate data usage
                $bytesIn = intval($sessionData['bytes_in'] ?? 0);
                $bytesOut = intval($sessionData['bytes_out'] ?? 0);
                $usedBytes = $bytesIn + $bytesOut;
                $limitBytes = intval($sessionData['limit_bytes'] ?? 0);
                $remainingBytes = intval($sessionData['remaining_bytes'] ?? -1);
                
                // Calculate current session speed
                $speed = $this->calculateSpeed($usedBytes, $sessionData['uptime'] ?? '00:00:00');
                
                // Store user data in cache using phone number as key
                // This matches your DashboardController format: "user_session:{$user->phone}"
                $cacheKey = "user_session:{$username}";
                $userData = [
                    'data_used' => $usedBytes,
                    'current_speed' => $speed,
                    'connection_status' => 'active',
                    'ip_address' => $sessionData['ip'] ?? null,
                    'uptime' => $sessionData['uptime'] ?? '00:00:00',
                    'remaining_bytes' => $remainingBytes,
                    'limit_bytes' => $limitBytes,
                    'last_updated' => now()->toDateTimeString()
                ];
                
                Cache::put($cacheKey, $userData, now()->addMinutes(10));
                $usersCached++;
            }
        }
        
        // Store aggregated data for quick dashboard access
        Cache::put('router_summary', [
            'total_users' => $usersCached,
            'routers' => $routers,
            'last_sync' => now()->toDateTimeString()
        ], now()->addMinutes(10));
        
        return [
            'users_cached' => $usersCached,
            'routers' => $routers
        ];
    }
    
    private function calculateSpeed($bytes, $uptime)
    {
        // Parse uptime (format: HH:MM:SS)
        $parts = explode(':', $uptime);
        if (count($parts) !== 3) return 0;
        
        $seconds = ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        if ($seconds == 0) return 0;
        
        // Speed in Mbps
        $bitsPerSecond = ($bytes * 8) / $seconds;
        $mbps = $bitsPerSecond / (1024 * 1024);
        
        return round($mbps, 2);
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
    
    private function normalizeNewFormat($data)
    {
        // Convert new format to standard format
        // Input: { "router": "name", "users": [...] }
        // Output: [{ "router_name": "name", "online_users": [...] }]
        
        $routerName = $data['router'] ?? 'Unknown';
        $users = $data['users'] ?? [];
        
        // Transform users to match expected format
        $onlineUsers = [];
        foreach ($users as $user) {
            $onlineUsers[] = [
                'user' => $user['user'] ?? null,
                'ip' => $user['ip'] ?? null,
                'mac' => $user['mac'] ?? null,
                'profile' => $user['profile'] ?? null,
                'uptime' => '00:00:00', // Not provided in new format
                'bytes_in' => 0,
                'bytes_out' => 0,
                'used_bytes' => intval($user['used'] ?? 0),
                'limit_bytes' => intval($user['limit'] ?? 0),
                'remaining_bytes' => intval($user['limit'] ?? 0) - intval($user['used'] ?? 0),
                'online' => $user['online'] ?? false,
                'validity' => $user['validity'] ?? null
            ];
        }
        
        return [[
            'router_name' => $routerName,
            'router_ip' => '0.0.0.0/0', // Not provided
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'online_users' => $onlineUsers
        ]];
    }
}