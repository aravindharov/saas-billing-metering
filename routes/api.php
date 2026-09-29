<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Versioned API routes live under `/api/v1`. The health endpoint is
| unversioned and unauthenticated for infrastructure probes.
|
| Business endpoints will be added in later phases.
*/

Route::get('/health', HealthController::class)->name('api.health');

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    // Phase 1+ endpoints will be registered here.
});
