<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;

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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Paystack payment route (authenticated)
    Route::post('/pay', [PaymentController::class, 'redirectToGateway'])->name('pay');

    Route::get('/fix-admin', function () {
        $user = \App\Models\User::where('email', 'admin@hifastlink.com')->first();
        
        if (!$user) return "User not found";

        // 1. Assign Role
        $user->assignRole('super_admin');

        // 2. Assign a Dummy Radius Username
        // (Ensure this column exists in your users table, usually 'username' or 'radius_username')
        $user->username = 'admin'; 
        $user->save();
        
        return "Fixed! Role: Super Admin, Username: admin";
    });
});

// Paystack callback (public - Paystack redirects the browser back here)
Route::get('/payment/callback', [PaymentController::class, 'handleGatewayCallback'])->name('payment.callback');

require __DIR__.'/auth.php';
