<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RouterController extends Controller
{

    /**
     * Return router login credentials for the authenticated user.
     */
    public function credentials(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Subscription check
        if (!method_exists($user, 'isSubscriptionActive') || !$user->isSubscriptionActive()) {
            return response()->json(['message' => 'No active subscription. Please renew to connect.'], 422);
        }

        // Check for required credentials
        if (empty($user->username) || empty($user->radius_password)) {
            Log::error('Router credentials missing for user id: ' . $user->id);
            return response()->json(['message' => 'User credentials missing. Please contact support.'], 500);
        }

        // Determine login URL (use services.mikrotik.gateway or default MikroTik gateway)
        $gateway = config('services.mikrotik.gateway') ?: '192.168.88.1';

        $loginUrl = (strpos($gateway, '://') === false ? 'http://' . $gateway : $gateway);
        if (!preg_match('#/login#', $loginUrl)) {
            $loginUrl = rtrim($loginUrl, '/') . '/login';
        }

        return response()->json([
            'username' => $user->username,
            'password' => $user->radius_password,
            'login_url' => $loginUrl,
        ]);
    }
}
