<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register admin middleware
        $this->app->make('router')->aliasMiddleware('admin', \App\Http\Middleware\AdminMiddleware::class);

        // Limit verification email resends to 3 per hour per user (anti-spam)
        RateLimiter::for('verification-email', function (Request $request) {
            return Limit::perHour(3)->by($request->user()?->id ?: $request->ip());
        });

        // Observe user plan changes to trigger plan sync to RADIUS tables.
        \App\Models\User::observe(\App\Observers\UserObserver::class);

        // Observe router changes to sync with RADIUS NAS table
        \App\Models\Router::observe(\App\Observers\RouterObserver::class);

        // Safety net: auto-populate family_limit / is_family_admin on the User
        // whenever a Subscription is created or its plan changes.
        \App\Models\Subscription::observe(\App\Observers\SubscriptionObserver::class);

        // Ensure Livewire components are registered (explicit registration to avoid auto-discovery issues)
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('user-dashboard', \App\Http\Livewire\UserDashboard::class);
            \Livewire\Livewire::component('captive-auth', \App\Http\Livewire\CaptiveAuth::class);
        }
    }
}
