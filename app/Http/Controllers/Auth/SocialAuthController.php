<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\RadCheck;
use App\Models\User;
use App\Services\FreeTrialService;
use App\Services\PlanSyncService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        $driver = Socialite::driver('google')->stateless();

        $stateData = [];
        foreach (['bonus', 'router', 'mac', 'ip'] as $key) {
            if (request()->filled($key)) {
                $stateData[$key] = request($key);
            }
        }
        $linkLogin = request('link-login') ?? request('link_login') ?? request('link-login-only');
        if ($linkLogin) {
            $stateData['link_login'] = $linkLogin;
        }

        if (! empty($stateData)) {
            $driver->with(['state' => base64_encode(json_encode($stateData))]);
            session(['oauth_state' => $stateData]);
        }

        return $driver->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse|Response
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            Log::error('Google OAuth sign-in failed: '.$e->getMessage(), [
                'exception' => $e,
                'request'   => request()->all(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Google sign-in failed: '.$e->getMessage());
        }

        $email = strtolower($googleUser->getEmail());

        // Find by google_id first, then fall back to matching email
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $email)->first();

        // Decode preserved state (bonus, router, link_login, mac, ip)
        $stateData = session('oauth_state', []);
        if (request('state')) {
            try {
                $decoded = json_decode(base64_decode(request('state')), true);
                if (is_array($decoded)) {
                    $stateData = array_merge($stateData, $decoded);
                }
            } catch (\Throwable $t) {
                // ignore
            }
        }

        $router    = $stateData['router'] ?? null;
        $linkLogin = $stateData['link_login'] ?? null;
        $mac       = $stateData['mac'] ?? null;
        $ip        = $stateData['ip'] ?? request()->ip();

        if ($user) {
            // Link the Google account if this is the first time signing in via Google
            $dirty = false;
            if (! $user->google_id) {
                $user->google_id = $googleUser->getId();
                $dirty = true;
            }
            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
                $dirty = true;
            }
            if (! $user->radius_password) {
                $user->radius_password = Str::random(16);
                $dirty = true;
            }
            if ($dirty) {
                $user->save();
            }

            // Apply free trial if new user hasn't claimed it yet and toggle is on
            FreeTrialService::apply($user, $router);
            PlanSyncService::syncUserPlan($user);
        } else {
            // New user — auto-generate a username from the email local part
            $randomRadiusPassword = Str::random(16);
            $user = User::create([
                'name'              => $googleUser->getName() ?: 'Google User',
                'username'          => $this->generateUsername($email),
                'email'             => $email,
                'email_verified_at' => now(), // Google verified the email
                'google_id'         => $googleUser->getId(),
                'password'          => Hash::make(Str::random(32)),
                'radius_password'   => $randomRadiusPassword,
                'data_limit'        => 1073741824, // 1 GB initial limit
                'plan_expiry'       => now()->addDays(30),
                'connection_status' => 'active',
            ]);

            event(new Registered($user));

            // Automatically apply free trial if enabled
            FreeTrialService::apply($user, $router);

            // Write credentials to radcheck immediately
            PlanSyncService::syncUserPlan($user);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();
        session()->forget('oauth_state');

        // Upsert device record if MAC is present
        if ($mac) {
            try {
                Device::upsertFromLogin(
                    $user,
                    $mac,
                    $router,
                    $ip,
                    request()->userAgent()
                );
            } catch (\Throwable $e) {
                Log::warning('SocialAuthController: Device upsert failed: ' . $e->getMessage());
            }
        }

        // If user came from captive portal, bridge directly to router for instant Wi-Fi access!
        if ($linkLogin) {
            $rad = RadCheck::where('username', $user->username)
                ->where('attribute', 'Cleartext-Password')
                ->first();
            $password = $rad?->value ?? $user->radius_password;

            if ($password) {
                return response()->view('hotspot.redirect_to_router', [
                    'username'      => $user->username,
                    'password'      => $password,
                    'link_login'    => $linkLogin,
                    'link_orig'     => route('app.home'),
                    'mac'           => $mac,
                    'ip'            => $ip,
                    'router'        => $router,
                    'force_connect' => true,
                ]);
            }
        }

        // Avoid infinite redirect loop back to login if url.intended points to auth routes
        $intended = session()->pull('url.intended');
        if (! $intended || str_contains($intended, '/login') || str_contains($intended, '/auth/google')) {
            $intended = $user->isAdmin() ? $user->homeUrl() : route('app.home');
        }

        return redirect()->to($intended);
    }

    private function generateUsername(string $email): string
    {
        // Strip non-alphanumeric characters (username field is alpha_num only)
        $base = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]) ?: 'user';

        $username = $base;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $i++;
        }

        return $username;
    }
}
