<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckHotspotMac
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Catch the bounce-back from the MikroTik router
        if ($request->has('mac')) {
            // Save the MAC securely to the Laravel session
            $request->session()->put('hotspot_mac', $request->query('mac'));
            
            // Optional: You can also grab the router identity if needed
            if ($request->has('router')) {
                $request->session()->put('hotspot_router', $request->query('router'));
            }

            // Strip the query parameters from the URL for a clean user experience
            return redirect()->url($request->url()); 
        }

        // 2. If the session already has the MAC, let the request pass to the dashboard
        if ($request->session()->has('hotspot_mac')) {
            return $next($request);
        }

        // 3. Trigger the Micro-Bounce
        // If we reach here, Chrome doesn't know the MAC. We must ask the router.
        
        // Grab the gateway exactly as you do in your RouterController
        $gateway = env('MIKROTIK_GATEWAY', 'http://login.wifi');
        $gatewayUrl = rtrim((strpos($gateway, '://') === false ? 'http://' . $gateway : $gateway), '/');
        
        // Tell the router exactly where to send the user back to (the current URL)
        $returnUrl = urlencode($request->fullUrl());

        // Redirect to the router. Because the user is already authenticated at Layer 2,
        // the router will skip the login page and instantly serve 'status.html'
        return redirect()->away($gatewayUrl . '/login?dst=' . $returnUrl);
    }
}