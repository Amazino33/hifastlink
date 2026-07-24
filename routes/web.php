<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\VoucherController;
use App\Models\RadCheck;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// ============================================================
// PUBLIC PAGES
// ============================================================

Route::get('/', fn () => view('welcome'))->name('home');
Route::get('/about-us', fn () => view('about'))->name('about');

Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('contact.submit');
Route::get('/coverage', [PageController::class, 'coverage'])->name('coverage');
Route::get('/help', [PageController::class, 'help'])->name('help');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/installation-guide', [PageController::class, 'installation'])->name('installation');
Route::get('/network-status', [PageController::class, 'status'])->name('status');

// ============================================================
// VOUCHERS — public endpoints (no auth)
// ============================================================

Route::post('/voucher/check-input', [VoucherController::class, 'checkInput'])->name('voucher.check-input');
Route::get('/voucher/success', [VoucherController::class, 'success'])->name('voucher.success');
Route::get('/pharmacy-voucher', fn () => view('pharmacy-voucher'))->name('pharmacy.voucher');

// Simple connected page — public, no auth, used as MikroTik dst after login
Route::get('/connected', fn () => view('hotspot.connected'))->name('captive.connected');

// PWA connect app — installable, handles auto-connect on hotspot
Route::get('/connect-app', fn () => view('connect-app'))->name('captive.app');

// Captive portal bridge — full page redirect so JS + meta refresh work in captive mini-browsers
Route::get('/captive-bridge', function () {
    $username  = session()->pull('bridge_username');
    $password  = session()->pull('bridge_password');
    $linkLogin = session()->pull('bridge_link_login');
    $linkOrig  = session()->pull('bridge_link_orig');
    $mac       = session()->pull('bridge_mac');
    $ip        = session()->pull('bridge_ip');
    $router    = session()->pull('bridge_router');

    if (! $username || ! $linkLogin) {
        return redirect()->route('dashboard');
    }

    return view('hotspot.redirect_to_router', [
        'username'   => $username,
        'password'   => $password,
        'link_login' => $linkLogin,
        'link_orig'  => $linkOrig,
        'mac'        => $mac,
        'ip'         => $ip,
        'router'     => $router,
    ]);
})->name('captive.bridge');

// ============================================================
// PAYMENTS — public (Paystack redirects browser here)
// ============================================================

Route::get('/payment/callback', [PaymentController::class, 'handleGatewayCallback'])->name('payment.callback');

// ============================================================
// AUTHENTICATED ROUTES
// ============================================================

Route::middleware(['auth', 'verified'])->group(function () {

    // ── Dashboard ─────────────────────────────────────────────
    Route::get('/dashboard', \App\Http\Livewire\UserDashboard::class)
        ->middleware(\App\Http\Middleware\CheckHotspotMac::class)
        ->name('dashboard');

    Route::get('/dashboard/realtime-data', [DashboardController::class, 'getRealtimeData'])->name('dashboard.realtime');
    Route::post('/dashboard/connect', [DashboardController::class, 'connectToRouter'])->name('dashboard.connect');

    // ── Profile ───────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Router & Hotspot ──────────────────────────────────────
    Route::post('/router/credentials', [RouterController::class, 'credentials'])->name('router.credentials');
    Route::post('/router/bridge-login', [RouterController::class, 'bridgeLogin'])->name('router.bridge_login');
    Route::get('/connect-bridge', [\App\Http\Controllers\HotspotController::class, 'connectBridge'])->name('connect.bridge');
    Route::get('/disconnect-bridge', [\App\Http\Controllers\HotspotController::class, 'disconnectBridge'])->name('disconnect.bridge');
    Route::post('/user/disconnect', [\App\Http\Controllers\NetworkController::class, 'disconnectUser'])->name('user.disconnect');

    // ── Plans & Billing ───────────────────────────────────────
    Route::post('/pay', [PaymentController::class, 'redirectToGateway'])->name('pay');
    Route::get('/request-custom-plans', \App\Livewire\RequestCustomPlans::class)->name('request-custom-plans');

    // ── Vouchers ──────────────────────────────────────────────
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::post('/vouchers/generate', [VoucherController::class, 'generate'])->name('vouchers.generate');
    Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
    Route::delete('/vouchers', [VoucherController::class, 'bulkDestroy'])->name('vouchers.bulk-destroy');

    // ── Family ────────────────────────────────────────────────
    Route::get('/family', \App\Http\Livewire\FamilyManager::class)->name('family');

    // ── API ───────────────────────────────────────────────────
    Route::get('/api/admin/stats', [StatsController::class, 'getStats'])->name('api.admin.stats');

    Route::get('/affiliate/router/analytics', [\App\Http\Controllers\AffiliateController::class, 'routerAnalytics'])
        ->name('affiliate.router.analytics');
});

// ============================================================
// ADMIN ROUTES
// ============================================================

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/routers/{router}/download-config', [RouterController::class, 'downloadConfig'])->name('router.download');
    Route::get('/unmatched-router-refs', [\App\Http\Controllers\AdminRouterController::class, 'index'])->name('router.unmatched');
    Route::post('/unmatched-router-refs/assign', [\App\Http\Controllers\AdminRouterController::class, 'assign'])->name('router.assign');
});

// ============================================================
// UTILITY / TEMPORARY — remove before production
// ============================================================

Route::get('/clear-config', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    return 'Config cleared.';
});

Route::middleware('auth')->group(function () {
    Route::get('/fix-admin', function () {
        $user = \App\Models\User::where('email', 'amazino33@gmail.com')->first();
        if (!$user) return 'User not found.';
        if (empty($user->username)) return 'User has no username.';

        RadCheck::firstOrCreate(
            ['username' => $user->username],
            ['attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $user->radius_password ?? '123456']
        );

        return "SUCCESS: '{$user->username}' added to RADIUS.";
    });

    Route::get('/debug-router', function () {
        $user = auth()->user();
        return [
            'user_id'        => $user->id,
            'user_router_id' => $user->router_id,
            'routers'        => \App\Models\Router::all()->map(fn ($r) => [
                'id'             => $r->id,
                'name'           => $r->name,
                'nas_identifier' => $r->nas_identifier,
            ]),
        ];
    });

    Route::get('/debug-janitor', function () {
        $targetUser = 'princewill';
        $user = \App\Models\User::where('username', $targetUser)->first();
        if (!$user) return "User '$targetUser' not found.";

        $serverTime = now();
        $expiryTime = \Carbon\Carbon::parse($user->plan_expiry);
        $isExpired  = $serverTime->greaterThan($expiryTime);

        $session = \DB::table('radacct')
            ->where('username', $targetUser)
            ->whereNull('acctstoptime')
            ->first();

        return response()->json([
            'server_time'    => $serverTime->format('Y-m-d H:i:s'),
            'expiry_time'    => $expiryTime->format('Y-m-d H:i:s'),
            'hours_left'     => $serverTime->diffInHours($expiryTime, false),
            'is_expired'     => $isExpired,
            'active_session' => $session ? true : false,
        ]);
    });
});

// TEMP: diagnose + fix admin radusergroup — REMOVE AFTER USE
Route::get('/tmp-fix-admin-group', function () {
    $admins = \App\Models\User::where('role', 'admin')->orWhere('role', 'super_admin')->get(['id', 'username', 'role', 'plan_id']);

    $results = [];
    foreach ($admins as $admin) {
        if (! $admin->username) continue;

        $groupRow = DB::table('radusergroup')->where('username', $admin->username)->first();
        $groupReplies = $groupRow
            ? DB::table('radgroupreply')->where('groupname', $groupRow->groupname)->get()
            : collect();

        $deleted = DB::table('radusergroup')->where('username', $admin->username)->delete();

        $results[$admin->username] = [
            'role'         => $admin->role,
            'plan_id'      => $admin->plan_id,
            'group_was'    => $groupRow?->groupname,
            'group_replies'=> $groupReplies,
            'rows_deleted' => $deleted,
            'radreply_now' => DB::table('radreply')->where('username', $admin->username)->get(),
        ];
    }

    return response()->json($results);
});

// ============================================================
// INCLUDES
// ============================================================

require __DIR__ . '/diagnostic.php';
require __DIR__ . '/auth.php';