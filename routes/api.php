<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataImportController;
use App\Http\Controllers\RadiusController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/import-data', [DataImportController::class, 'import']);

// RADIUS Accounting Endpoint
Route::post('/radius/accounting', [RadiusController::class, 'handleAccounting']);