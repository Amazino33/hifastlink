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