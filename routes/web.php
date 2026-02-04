<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RouterController;
use App\Models\RadCheck;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/about-us', function () {
    return view('about');
})->name('about');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', \App\Http\Livewire\UserDashboard::class)->name('dashboard');
    Route::get('/dashboard/realtime-data', [DashboardController::class, 'getRealtimeData'])->name('dashboard.realtime');
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

require __DIR__.'/auth.php';
