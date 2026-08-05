<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Plan;
use App\Models\Router;
use App\Models\User;
use App\Services\PlanSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class FreeWifiController extends Controller
{
    public function show(Request $request, string $router): mixed
    {
        $router = Router::where('nas_identifier', $router)
            ->where('is_active', true)
            ->firstOrFail();

        // Already logged in — send them straight to the dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard', ['router' => $router->nas_identifier]);
        }

        $enabled = AppSetting::bool('free_wifi_enabled', false);

        return view('free-wifi', [
            'router'  => $router,
            'enabled' => $enabled,
            'mac'     => $request->query('mac'),
            'ip'      => $request->query('ip'),
        ]);
    }

    public function register(Request $request, string $routerIdentifier): mixed
    {
        $router = Router::where('nas_identifier', $routerIdentifier)
            ->where('is_active', true)
            ->firstOrFail();

        // Bail early if the offer is disabled — before any DB work
        if (! AppSetting::bool('free_wifi_enabled', false)) {
            return back()->withErrors(['phone' => 'The free trial offer is not currently available.']);
        }

        // IP-based rate limit: 5 attempts per 10 minutes
        $key = 'free-wifi:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['phone' => "Too many attempts. Please wait {$seconds} seconds and try again."]);
        }
        RateLimiter::hit($key, 600);

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'mac'   => ['nullable', 'string', 'max:30'],
        ]);

        $planId    = AppSetting::get('free_wifi_plan_id');
        $trialPlan = $planId ? Plan::where('id', $planId)->where('is_active', true)->first() : null;

        if (! $trialPlan) {
            return back()->withErrors(['phone' => 'Free trial is temporarily unavailable. Please contact support.']);
        }

        $normalizedPhone = User::normalizePhone($validated['phone']);

        $existingUser = User::where('phone', $normalizedPhone)->first();

        // Abuse check: same phone has already claimed a trial
        if ($existingUser && $existingUser->free_trial_claimed_at !== null) {
            return back()
                ->withInput()
                ->withErrors(['phone' => 'This phone number has already used the free trial.']);
        }

        // If existing user has an active paid plan, just log them in without touching it
        if ($existingUser && $existingUser->plan_id && $existingUser->plan_expiry?->isFuture()) {
            Auth::login($existingUser);
            return redirect()->route('dashboard', ['router' => $router->nas_identifier]);
        }

        if ($existingUser) {
            $user = $existingUser;
            $user->name = $validated['name'];
        } else {
            // Create a new user
            $username = $this->generateUsername($normalizedPhone);
            $password = Str::random(12);

            $user = User::create([
                'name'           => $validated['name'],
                'phone'          => $normalizedPhone,
                'username'       => $username,
                'password'       => Hash::make($password),
                'radius_password'=> Str::random(16),
                'router_id'      => $router->id,
            ]);
        }

        // Assign free trial plan
        $user->plan_id            = $trialPlan->id;
        $user->data_used          = 0;
        $user->data_limit         = null; // unlimited
        $user->plan_expiry        = now()->addDays($trialPlan->validity_days ?: 1);
        $user->plan_started_at    = now();
        $user->free_trial_claimed_at = now();
        $user->save(); // triggers observer → RADIUS sync

        // Force RADIUS sync in case observer didn't fire on new user
        PlanSyncService::syncUserPlan($user->fresh());

        Auth::login($user);

        // If mac is present (captive portal context), pass it so dashboard auto-connects
        $params = ['router' => $router->nas_identifier];
        if ($request->input('mac')) {
            $params['mac'] = $request->input('mac');
        }

        return redirect()->route('dashboard', $params);
    }

    private function generateUsername(string $normalizedPhone): string
    {
        // e.g. +2348012345678 → wifi12345678
        $digits = preg_replace('/\D/', '', $normalizedPhone);
        $base   = 'wifi' . substr($digits, -10);

        $username = $base;
        $suffix   = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $suffix++;
        }

        return $username;
    }
}
