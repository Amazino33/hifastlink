<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Admins/staff bypass verification
        if ($user->hasUnrestrictedAccess()) {
            return $next($request);
        }

        // Google OAuth users are verified via Google
        if ($user->google_id) {
            return $next($request);
        }

        // Phone verified OR email verified — either works
        if ($user->phone_verified_at || $user->email_verified_at) {
            return $next($request);
        }

        return redirect()->route('login');
    }
}
