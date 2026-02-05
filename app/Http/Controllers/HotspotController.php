<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\RadCheck;

class HotspotController extends Controller
{
    /**
     * Show the bridge redirect view which auto-submits to the router (GET). 
     */
    public function connectBridge(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('dashboard')->with('error', 'Please sign in.');
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
            return redirect()->route('dashboard')->with('error', 'Please buy a plan.');
        }

        // Self-repair plan_id if missing
        if (isset($validSubscription->plan_id) && empty($user->plan_id) && $validSubscription->plan_id) {
            try {
                $user->plan_id = $validSubscription->plan_id;
                $user->save();
                Log::info('Repaired missing plan_id for user '.$user->id.' via connect-bridge.');
            } catch (\Exception $e) {
                Log::warning('Failed to repair plan_id for user '.$user->id.': '.$e->getMessage());
            }
        }

        $rad = RadCheck::where('username', $user->username)->where('attribute', 'Cleartext-Password')->first();
        $password = $rad ? $rad->value : ($user->radius_password ?? null);

        if (! $password) {
            return redirect()->route('dashboard')->with('error', 'Missing router password. Please contact support.');
        }

        // Router URL
        $gateway = config('services.mikrotik.gateway') ?? env('MIKROTIK_LOGIN_URL') ?? 'http://192.168.88.1/login';
        $loginUrl = (strpos($gateway, '://') === false ? 'http://' . $gateway : $gateway);
        if (! preg_match('#/login#', $loginUrl)) {
            $loginUrl = rtrim($loginUrl, '/') . '/login';
        }

        // link_login is what the bridge/portal uses
        $link_login = $loginUrl;
        $link_orig = route('dashboard');

        return view('hotspot.redirect_to_router', [
            'username' => $user->username,
            'password' => $password,
            'link_login' => $link_login,
            'link_orig' => $link_orig,
        ]);
    }

    /**
     * Disconnect from router (logout)
     */
    public function disconnectBridge(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('dashboard')->with('error', 'Please sign in.');
        }

        // Router logout URL - typically accessed via GET
        $gateway = config('services.mikrotik.gateway') ?? env('MIKROTIK_LOGIN_URL') ?? 'http://192.168.88.1/login';
        $logoutUrl = (strpos($gateway, '://') === false ? 'http://' . $gateway : $gateway);
        $logoutUrl = rtrim(str_replace('/login', '', $logoutUrl), '/') . '/logout';

        return view('hotspot.disconnect_from_router', [
            'logout_url' => $logoutUrl,
            'redirect_url' => route('dashboard'),
        ]);
    }
}
