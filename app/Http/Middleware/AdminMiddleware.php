<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            abort(403, 'Access denied');
        }

        $user = auth()->user();

        // Allow if explicit flag is set
        if (isset($user->is_admin) && $user->is_admin) {
            return $next($request);
        }

        // Allow the master email or users with admin roles (Spatie roles)
        if ($user->email === 'amazino33@gmail.com' || (method_exists($user, 'hasRole') && ($user->hasRole('super_admin') || $user->hasRole('admin')))) {
            return $next($request);
        }

        // Filament panel check (if implemented by user model)
        if (method_exists($user, 'canAccessPanel') && $user->canAccessPanel(app(\Filament\Panel::class) ?? null)) {
            return $next($request);
        }

        abort(403, 'Access denied');
    }
}