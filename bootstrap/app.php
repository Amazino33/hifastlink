<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust the reverse proxy (Nginx) so Laravel sees the correct https:// scheme
        // on incoming requests, which is required for signed URL verification to work.
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'payment/webhook', // OR 'paystack/webhook' - Check your route name
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsurePhoneIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Redirect invalid/expired email verification links to the notice page
        // instead of showing a bare 403. Most common cause: APP_URL http/https mismatch.
        $exceptions->render(function (\Illuminate\Routing\Exceptions\InvalidSignatureException $e, \Illuminate\Http\Request $request) {
            if ($request->routeIs('verification.verify')) {
                return redirect()->route('verification.notice')
                    ->with('error', 'This verification link is invalid or has expired. Please request a new one below.');
            }
        });

        // Capture all unhandled exceptions to system_logs so they appear in the admin panel
        // even when APP_DEBUG=false hides them from users.
        $exceptions->report(function (\Throwable $e) {
            // Skip noise — these are normal control-flow exceptions, not bugs
            if ($e instanceof \Illuminate\Validation\ValidationException) return false;
            if ($e instanceof \Illuminate\Auth\AuthenticationException) return false;
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) return false;
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) return false;
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) return false;

            \App\Models\SystemLog::capture($e);

            return false; // don't suppress default Laravel logging (storage/logs/laravel.log)
        });
    })->create();
