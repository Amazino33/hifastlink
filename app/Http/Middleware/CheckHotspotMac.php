<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckHotspotMac
{
    public function handle(Request $request, Closure $next)
    {
        $mac = $request->query('mac');

        // Safety Catch: Sometimes MikroTik fails to parse variables and outputs literal strings
        if ($mac === '$(mac)') {
            $mac = null; 
        }

        // 1. Catch the bounce-back or initial login from the MikroTik router
        if (!empty($mac)) {
            // Save the MAC securely to the Laravel session
            $request->session()->put('hotspot_mac', $mac);
            
            if ($request->has('router')) {
                $request->session()->put('hotspot_router', $request->query('router'));
            }

            // FIX: Let the request pass directly to the dashboard. 
            // Do NOT redirect here, as captive portals will drop the session cookie.
            return $next($request); 
        }

        // 2. If the session already has the MAC (e.g., normal Chrome navigation)
        if ($request->session()->has('hotspot_mac')) {
            return $next($request);
        }

        // 3. Trigger the Micro-Bounce
        // If we reach here, the browser doesn't know the MAC. Bounce to the router.
        $gateway = env('MIKROTIK_GATEWAY', 'http://login.wifi');
        $gatewayUrl = rtrim((strpos($gateway, '://') === false ? 'http://' . $gateway : $gateway), '/');
        
        $returnUrl = urlencode($request->fullUrl());

        return redirect()->away($gatewayUrl . '/login?dst=' . $returnUrl);
    }
}