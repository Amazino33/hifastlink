<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Router;
use Illuminate\Support\Facades\Log;

class RouterHeartbeatController extends Controller
{
    /**
     * GET /api/routers/heartbeat?identity={nas_identifier}&token={optional}
     */
    public function heartbeat(Request $request)
    {
        $identity = $request->query('identity');

        if (! $identity) {
            return response()->json(['success' => false, 'message' => 'Missing identity parameter'], 400);
        }

        // Optional static token protection
        $expected = env('ROUTER_HEARTBEAT_TOKEN');
        if ($expected) {
            $provided = $request->query('token');
            if (! $provided || ! hash_equals((string) $expected, (string) $provided)) {
                return response()->json(['success' => false, 'message' => 'Invalid token'], 401);
            }
        }

        $router = Router::where('nas_identifier', $identity)->first();

        if (! $router) {
            return response()->json(['success' => false, 'message' => 'Router not found'], 404);
        }

        $router->last_seen_at = now();
        $router->save();

        Log::info('Router heartbeat recorded', ['router' => $router->nas_identifier, 'ip' => $request->ip()]);

        return response()->json(['success' => true, 'message' => 'Heartbeat recorded']);
    }
}
