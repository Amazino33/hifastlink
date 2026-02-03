<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Attempt to log the user into the router via RADIUS bridge using the raw password
        try {
            $user = \Illuminate\Support\Facades\Auth::user();
            $rawPassword = $request->string('password');

            if ($user && $rawPassword) {
                $bridgeUrl = rtrim(env('RADIUS_BRIDGE_URL', ''), '/');
                $secret = env('RADIUS_SECRET_KEY', null);

                if ($bridgeUrl && $secret) {
                    $resp = \Illuminate\Support\Facades\Http::post($bridgeUrl . '/login', [
                        'username' => $user->username,
                        'password' => $rawPassword,
                        'secret' => $secret,
                    ]);

                    if ($resp->successful() && ($resp->json('success') ?? false)) {
                        \Illuminate\Support\Facades\Log::info("Router login successful for user {$user->username}");
                    } else {
                        \Illuminate\Support\Facades\Log::warning("Router login failed for user {$user->username}", ['resp' => $resp->body()]);
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Router login error: ' . $e->getMessage());
        }

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
