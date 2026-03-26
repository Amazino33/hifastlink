<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckHotspotMac
{
    public function handle(Request $request, Closure $next)
    {
        // 1. NEVER intercept AJAX/Fetch or non-GET requests. 
        if ($request->ajax() || $request->wantsJson() || !$request->isMethod('get')) {
            return $next($request);
        }

        $mac = $request->query('mac');

        // Safety Catch: If MikroTik fails to parse the variable and outputs the literal string
        if ($mac === '$(mac)') {
            $mac = null; 
        }

        // 2. Catch the return trip from the MikroTik router
        if (!empty($mac)) {
            // Save to session and FORCE write it so it isn't lost
            $request->session()->put('hotspot_mac', $mac);
            
            if ($request->has('router')) {
                $request->session()->put('hotspot_router', $request->query('router'));
            }
            
            session()->save();

            // Let the request pass directly to the dashboard. 
            // DO NOT REDIRECT HERE, or the captive portal will drop the session cookie.
            return $next($request); 
        }

        // 3. We have the MAC in session, let them see the dashboard!
        if ($request->session()->has('hotspot_mac')) {
            return $next($request);
        }

        // ✅ Only bounce if the user is actually on the hotspot subnet
        if (!$this->isOnHotspotNetwork($request)) {
            return $next($request); // External user — let them see the dashboard normally
        }

        // 4. THE LOOP BREAKER: If we already bounced them and it failed, stop here.
        // This ensures the page eventually loads even if cookies are strictly blocked.
        if ($request->query('bounced') == '1') {
            // ✅ Persist so future requests don't bounce again
            $request->session()->put('hotspot_mac', 'unknown');
            $request->session()->put('hotspot_connected_at', now()->toISOString());
            session()->save();
            return $next($request);
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

    private function isOnHotspotNetwork(Request $request): bool
    {
        $ip     = $request->ip();
        $subnet = env('HOTSPOT_SUBNET', '192.168.88.');   // adjust if your subnet differs

        return str_starts_with($ip, $subnet);
    }
}