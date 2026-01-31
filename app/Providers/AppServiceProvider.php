<?php

namespace App\Providers;

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
        $router = $this->app['router'];
        $router->aliasMiddleware('admin', \App\Http\Middleware\AdminMiddleware::class);

        // Observe user plan changes to trigger plan sync to RADIUS tables.
        \App\Models\User::observe(\App\Observers\UserObserver::class);

        // Ensure Livewire components are registered (explicit registration to avoid auto-discovery issues)
        if (class_exists(\Livewire\Livewire::class) && class_exists(\App\Http\Livewire\UserDashboard::class)) {
            \Livewire\Livewire::component('user-dashboard', \App\Http\Livewire\UserDashboard::class);
        }
    }
}
