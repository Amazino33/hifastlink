<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AdminController;
use App\Models\RadCheck;
use App\Http\Controllers\StatsController;

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

// TEMPORARY DEBUG ROUTE
Route::get('/debug-janitor', function () {
    // 1. Hardcode the user we are testing
    $targetUser = 'princewill'; // Make sure this matches exactly

    echo "<h1>Janitor Debug Report</h1>";

    // 2. Check Timezone & Expiry
    $user = \App\Models\User::where('username', $targetUser)->first();
    
    if (!$user) {
        return "User '$targetUser' not found in users table.";
    }

    $serverTime = now();
    $expiryTime = \Carbon\Carbon::parse($user->plan_expiry);
    
    echo "<b>Server Time (What Laravel sees):</b> " . $serverTime->format('Y-m-d H:i:s') . "<br>";
    echo "<b>User Expiry (Database):</b> " . $expiryTime->format('Y-m-d H:i:s') . "<br>";
    echo "<b>Time Difference:</b> " . $serverTime->diffInHours($expiryTime, false) . " Hours <br>";
    
    $isExpired = $serverTime->greaterThan($expiryTime);
    echo "<b>Is Expired?</b> " . ($isExpired ? "<span style='color:green; font-weight:bold;'>YES (Correct)</span>" : "<span style='color:red; font-weight:bold;'>NO (Server thinks they have time left)</span>") . "<br><br>";

    // 3. Check Session Availability (The "0 Users" Mystery)
    echo "<h3>Checking Active Session...</h3>";
    
    // Standard Query
    $session = \DB::table('radacct')
        ->where('username', $targetUser)
        ->whereNull('acctstoptime')
        ->first();

    if ($session) {
        echo "✅ Found active session for '$targetUser'.<br>";
        echo "MAC Address: " . $session->callingstationid . "<br>";
    } else {
        echo "❌ <b>NO active session found using standard query.</b><br>";
        echo "Possible Cause: Username case mismatch or Collation issue.<br>";
        
        // Try the "Raw" query to see if it finds it loosely
        $looseSession = \DB::table('radacct')
            ->whereRaw('username = ?', [$targetUser])
            ->whereNull('acctstoptime')
            ->first();
            
        if ($looseSession) {
            echo "⚠️ BUT found one using Raw SQL! This confirms a Collation/Case sensitivity bug.<br>";
        }
    }
});

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

// Admin stats JSON endpoint for PWA
Route::get('/api/admin/stats', [StatsController::class, 'getStats'])
    ->middleware(['auth'])
    ->name('api.admin.stats');

// Admin dashboard route
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.dashboard');

// Admin route: download generated router configuration
Route::get('/admin/routers/{router}/download-config', [RouterController::class, 'downloadConfig'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('router.download');

// Admin UI to review & assign unmatched router refs
Route::get('/admin/unmatched-router-refs', [\App\Http\Controllers\AdminRouterController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.router.unmatched');

Route::post('/admin/unmatched-router-refs/assign', [\App\Http\Controllers\AdminRouterController::class, 'assign'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.router.assign');

// Diagnostic routes (admin only)
require __DIR__.'/diagnostic.php';

require __DIR__.'/auth.php';
