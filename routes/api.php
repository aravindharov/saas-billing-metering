<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PlanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| `/api/v1` is the versioned contract. `auth:sanctum` authenticates the
| token holder, and `tenant` resolves the merchant from the authenticated
| user. The tenant context is always derived from the token, never from
| client-supplied input.
*/

Route::get('/health', HealthController::class)->name('api.health');

Route::prefix('v1')->name('api.v1.')->group(function (): void {

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');

    Route::middleware(['auth:sanctum', 'tenant'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        Route::apiResource('plans', PlanController::class);
    });
});
