<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\PageController;
use App\Models\RadCheck;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/about-us', function () {
    return view('about');
})->name('about');

// Public pages
Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('contact.submit');
Route::get('/coverage', [PageController::class, 'coverage'])->name('coverage');
Route::get('/help', [PageController::class, 'help'])->name('help');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/installation-guide', [PageController::class, 'installation'])->name('installation');
Route::get('/network-status', [PageController::class, 'status'])->name('status');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', \App\Http\Livewire\UserDashboard::class)->name('dashboard');
    Route::get('/dashboard/realtime-data', [DashboardController::class, 'getRealtimeData'])->name('dashboard.realtime');

    // Bridge connector that returns a page which redirects to router using GET (bridge flow)
    Route::get('/connect-bridge', [\App\Http\Controllers\HotspotController::class, 'connectBridge'])->name('connect.bridge')->middleware(['auth','web']);
    
    // Disconnect from router
    Route::get('/disconnect-bridge', [\App\Http\Controllers\HotspotController::class, 'disconnectBridge'])->name('disconnect.bridge')->middleware(['auth','web']);

    // Server-side disconnect to avoid router dependency
    Route::post('/user/disconnect', [\App\Http\Controllers\NetworkController::class, 'disconnectUser'])->name('user.disconnect')->middleware(['auth','web']);

    // Build GET-based router login link and return it to client
    Route::post('/dashboard/connect', [DashboardController::class, 'connectToRouter'])->name('dashboard.connect')->middleware(['auth','web']);
    Route::get('/family', \App\Http\Livewire\FamilyManager::class)->name('family');

    // Router connect credentials endpoint
    Route::post('/router/credentials', [RouterController::class, 'credentials'])->name('router.credentials');

    // Server-side bridge login for captive portal flow
    Route::post('/router/bridge-login', [RouterController::class, 'bridgeLogin'])->name('router.bridge_login');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Paystack payment route (authenticated)
    Route::post('/pay', [PaymentController::class, 'redirectToGateway'])->name('pay');

    Route::get('/fix-admin', function () {
        $user = \App\Models\User::where('email', 'amazino33@gmail.com')->first();
        
        if (!$user) return "User not found!";
        if (empty($user->username)) return "User has no username! Set one first.";

        // 2. Force Create the Radius Entry
        RadCheck::firstOrCreate(
            ['username' => $user->username],
            [
                'attribute' => 'Cleartext-Password',
                'op'        => ':=',
                'value'     => $user->radius_password ?? '123456' // Default password if none set
            ]
        );

        return "SUCCESS: User '{$user->username}' added to Radius Table!";
    });
});

Route::get('/clear-config', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    return "Config Cleared! New .env values are now active.";
});

// Paystack callback (public - Paystack redirects the browser back here)
Route::get('/payment/callback', [PaymentController::class, 'handleGatewayCallback'])->name('payment.callback');

// Admin route: download generated router configuration
Route::get('/admin/routers/{router}/download-config', [RouterController::class, 'downloadConfig'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('router.download');

// Diagnostic routes (admin only)
require __DIR__.'/diagnostic.php';

require __DIR__.'/auth.php';
