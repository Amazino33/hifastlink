<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FreeTrialService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        if (request('bonus')) {
            session(['oauth_bonus' => request('bonus'), 'oauth_router' => request('router')]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Google sign-in failed. Please try again.');
        }

        $email = strtolower($googleUser->getEmail());

        // Find by google_id first, then fall back to matching email
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            // Link the Google account if this is the first time signing in via Google
            if (! $user->google_id) {
                $user->google_id = $googleUser->getId();
                $user->save();
            }
        } else {
            // New user — auto-generate a username from the email local part
            $radiusPassword = Str::random(16);

            $user = User::create([
                'name'              => $googleUser->getName(),
                'username'          => $this->generateUsername($email),
                'email'             => $email,
                'email_verified_at' => now(), // Google already verified the email
                'google_id'         => $googleUser->getId(),
                'password'          => Hash::make(Str::random(32)),
                'radius_password'   => $radiusPassword,
                'data_limit'        => 1073741824, // 1 GB in bytes
                'plan_expiry'       => now()->addDays(30), // trial so PlanSyncService writes RADIUS credentials
                'connection_status' => 'active',
            ]);

            event(new Registered($user));
        }

        Auth::login($user, remember: true);

        if (session('oauth_bonus') === 'free_trial') {
            FreeTrialService::apply($user, session('oauth_router'));
            session()->forget(['oauth_bonus', 'oauth_router']);
        }

        // Admins → main dashboard; regular users → the customer PWA
        if ($user->isAdmin()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return redirect()->intended(route('app.home'));
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
