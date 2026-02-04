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
        dd($request->all());
        $request->authenticate();

        $request->session()->regenerate();

        // If captive portal parameters were provided by the router, return a bridge redirect view
        $linkLogin = $request->input('link_login') ?? $request->input('link-login') ?? null;
        $linkOrig = $request->input('link_orig') ?? $request->input('link-orig') ?? null;

        if ($linkLogin) {
            $user = \Illuminate\Support\Facades\Auth::user();

            // Prefer stored radius password; fallback to the password the user just submitted
            $password = $user->radius_password ?? $request->string('password');

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
