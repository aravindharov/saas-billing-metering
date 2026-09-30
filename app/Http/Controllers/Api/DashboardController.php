<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Models\Merchant;
use App\Services\Dashboard\DashboardService;
use Illuminate\Support\Facades\Gate;

final class DashboardController extends Controller
{
    public function show(Merchant $merchant, DashboardService $dashboard): DashboardResource
    {
        Gate::authorize('viewDashboard', $merchant);

        return new DashboardResource($dashboard->forMerchant($merchant));
    }
}
