<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckHotspotMac
{
    public function handle(Request $request, Closure $next)
    {
        // 1. NEVER intercept AJAX/Fetch requests. This causes invisible loops.
        if ($request->ajax() || $request->wantsJson() || !$request->isMethod('get')) {
            return $next($request);
        }

        $mac = $request->query('mac');

        // 2. Catch the return trip from the MikroTik router
        if (!empty($mac) && $mac !== '$(mac)') {
            // Save to session and FORCE write it so it isn't lost
            $request->session()->put('hotspot_mac', $mac);
            if ($request->has('router')) {
                $request->session()->put('hotspot_router', $request->query('router'));
            }
            session()->save();

            // Redirect to the clean URL (strips out the ?mac= string)
            return redirect($request->url()); 
        }

        // 3. We have the MAC in session, let them see the dashboard!
        if ($request->session()->has('hotspot_mac')) {
            return $next($request);
        }

        // 4. THE LOOP BREAKER: If we already bounced them and it failed, stop here.
        if ($request->query('bounced') == '1') {
            return $next($request); // Let them through to prevent infinite crashes
        }

        // 5. Trigger the Micro-Bounce
        $gateway = rtrim(env('MIKROTIK_GATEWAY', 'http://login.wifi'), '/');
        if (strpos($gateway, '://') === false) {
            $gateway = 'http://' . $gateway;
        }
        
        // Add ?bounced=1 so we know we already attempted this bounce
        $returnUrl = urlencode($request->url() . '?bounced=1');

        return redirect()->away($gateway . '/login?dst=' . $returnUrl);
    }
}