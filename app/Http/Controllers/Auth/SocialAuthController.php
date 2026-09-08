<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FreeTrialService;
use App\Services\PlanSyncService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
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
        if (request('bonus')) {
            $stateData['bonus'] = request('bonus');
        }
        if (request('router')) {
            $stateData['router'] = request('router');
        }

        if (! empty($stateData)) {
            $driver->with(['state' => base64_encode(json_encode($stateData))]);
            session(['oauth_bonus' => request('bonus'), 'oauth_router' => request('router')]);
        }

        return $driver->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
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

        if ($user) {
            // Link the Google account if this is the first time signing in via Google
            $dirty = false;
            if (! $user->google_id) {
                $user->google_id = $googleUser->getId();
                $dirty = true;
            }
            // Existing users created before Google OAuth might have no radius_password.
            // Generate one now so they can connect to the hotspot.
            if (! $user->radius_password) {
                $user->radius_password = Str::random(16);
                $dirty = true;
            }
            if ($dirty) {
                $user->save();
            }
            // Push credentials to radcheck immediately so FreeRADIUS can authenticate them.
            PlanSyncService::syncUserPlan($user);
        } else {
            // New user — auto-generate a username from the email local part
            $user = User::create([
                'name'              => $googleUser->getName(),
                'username'          => $this->generateUsername($email),
                'email'             => $email,
                'email_verified_at' => now(), // Google already verified the email
                'google_id'         => $googleUser->getId(),
                'password'          => Hash::make(Str::random(32)),
                'radius_password'   => Str::random(16),
                'data_limit'        => 1073741824, // 1 GB in bytes
                'plan_expiry'       => now()->addDays(30), // gives PlanSyncService a future expiry to work with
                'connection_status' => 'active',
            ]);

            event(new Registered($user));
            // Write credentials to radcheck immediately — don't wait for the scheduler.
            PlanSyncService::syncUserPlan($user);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        // Check for bonus & router in session or Google state query param
        $bonus = session('oauth_bonus');
        $router = session('oauth_router');

        if (! $bonus && request('state')) {
            try {
                $decoded = json_decode(base64_decode(request('state')), true);
                if (is_array($decoded)) {
                    $bonus = $decoded['bonus'] ?? null;
                    $router = $decoded['router'] ?? null;
                }
            } catch (\Throwable $t) {
                // ignore
            }
        }

        if ($bonus === 'free_trial') {
            FreeTrialService::apply($user, $router);
            session()->forget(['oauth_bonus', 'oauth_router']);
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
