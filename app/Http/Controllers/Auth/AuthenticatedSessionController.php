<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|Response
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Immediately handle captive portal bridge flow if requested by router parameters
        if ($request->filled('link_login')) {
            $user = \Illuminate\Support\Facades\Auth::user();

            $linkLogin = $request->input('link_login');
            // Force returning users to their dashboard after router completes login
            $linkOrig = route('dashboard');

            // Use clear_text_password if present on the user, otherwise use supplied password from request
            $password = $user->clear_text_password ?? $request->input('password');

            $mac = $request->input('mac');
            $ip = $request->input('ip');

            return response()->view('hotspot.redirect_to_router', [
                'username' => $user->username,
                'password' => $password,
                'link_login' => $linkLogin,
                'link_orig' => $linkOrig,
                'mac' => $mac,
                'ip' => $ip,
            ]);
        }

        // Otherwise continue with the normal dashboard redirect
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
