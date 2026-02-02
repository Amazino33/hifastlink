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

Route::middleware([])->group(function () {
    Route::get('/dashboard', \App\Http\Livewire\UserDashboard::class)->name('dashboard');
    Route::get('/dashboard/realtime-data', [DashboardController::class, 'getRealtimeData'])->name('dashboard.realtime');
    Route::get('/family', \App\Http\Livewire\FamilyManager::class)->name('family');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Paystack payment route (authenticated)
    Route::post('/pay', [PaymentController::class, 'redirectToGateway'])->middleware('auth')->name('pay');
});

// Paystack callback (public - Paystack redirects the browser back here)
Route::get('/payment/callback', [PaymentController::class, 'handleGatewayCallback'])->name('payment.callback');

require __DIR__.'/auth.php';
