<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RouterSpeedController extends Controller
{
    /**
     * Catch real-time bandwidth stats from MikroTik routers.
     * Route: GET /api/routers/speed
     */
    public function report(Request $request)
    {
        // The MikroTik script sends: ?identity=router_uyo&user=amazino33&bytes_in=1234&bytes_out=5678
        $identity = $request->query('identity');
        $user = $request->query('user');
        $bytesIn = (int) $request->query('bytes_in', 0);   // Router receiving = User Upload
        $bytesOut = (int) $request->query('bytes_out', 0); // Router sending = User Download

        if (!$identity || !$user) {
            return response()->json(['error' => 'Missing parameters'], 400);
        }

        // Create a unique cache key for this user's current session
        $cacheKey = "router_{$identity}_user_{$user}";
        
        // Fetch the previous tick's data to calculate the difference
        $previousData = Cache::get($cacheKey);

        $currentData = [
            'download_bps' => 0,
            'upload_bps' => 0,
            'total_bytes_in' => $bytesIn,
            'total_bytes_out' => $bytesOut,
            'timestamp' => now()->timestamp,
        ];

        if ($previousData) {
            // Calculate time elapsed (should be ~10 seconds based on our MikroTik scheduler)
            $timeElapsed = now()->timestamp - $previousData['timestamp'];
            
            if ($timeElapsed > 0) {
                // Get the difference in bytes since the last ping
                $downloadDiff = max(0, $bytesOut - $previousData['total_bytes_out']);
                $uploadDiff = max(0, $bytesIn - $previousData['total_bytes_in']);

                // Convert Bytes to Bits per second (bps) -> (Bytes * 8) / seconds
                $currentData['download_bps'] = ($downloadDiff * 8) / $timeElapsed;
                $currentData['upload_bps'] = ($uploadDiff * 8) / $timeElapsed;
            }
        }

        // Store this new data in the cache for 30 seconds. 
        // If the user logs out or the router goes offline, the data naturally deletes itself.
        Cache::put($cacheKey, $currentData, 30);

        // We return a simple 200 OK. The MikroTik script ignores the output anyway (output=none)
        return response()->json(['status' => 'success']);
    }
}