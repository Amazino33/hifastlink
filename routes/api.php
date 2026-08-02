<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataImportController;
use App\Http\Controllers\RadiusController;
use App\Http\Controllers\Api\RouterSpeedController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/import-data', [DataImportController::class, 'import']);

// RADIUS Accounting Endpoint
Route::post('/radius/accounting', [RadiusController::class, 'handleAccounting']);

// Router Heartbeat (public) - GET /api/routers/heartbeat?identity={nas_identifier}&token={optional}
use App\Http\Controllers\RouterHeartbeatController;
Route::get('/routers/heartbeat', [RouterHeartbeatController::class, 'heartbeat']);

Route::get('/routers/speed', [RouterSpeedController::class, 'report']);

// BasmelCare pharmacy integration — revoke a receipt's Wi-Fi access
use App\Http\Controllers\Api\PharmacyController;
Route::post('/pharmacy/revoke', [PharmacyController::class, 'revoke']);

// PWA connectivity probe — accessible via walled garden before hotspot auth.
// Also returns the router identifier so the PWA can call /connect-bridge directly.
Route::get('/ping', function (Request $request) {
    $ip     = $request->ip();
    $router = \App\Models\Router::where('ip_address', $ip)->where('is_active', true)->first();
    return response()->json([
        'ok'     => true,
        'router' => $router?->nas_identifier,
    ]);
});