<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Plans\ArchivePlan;
use App\Actions\Plans\CreatePlan;
use App\Actions\Plans\UpdatePlan;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Services\PlanCacheService;
use App\Tenancy\MerchantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class PlanController extends Controller
{
    public function index(Request $request, MerchantContext $context): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Plan::class);

        $query = Plan::where('merchant_id', $context->id());

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $plans = $query->orderBy('created_at', 'desc')->paginate(15);

        return PlanResource::collection($plans);
    }

    public function store(StorePlanRequest $request, MerchantContext $context, CreatePlan $action): JsonResponse
    {
        Gate::authorize('create', Plan::class);

        $plan = $action->execute($context->get(), $request->validated());

        return (new PlanResource($plan))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Plan $plan, MerchantContext $context, PlanCacheService $cache): PlanResource
    {
        Gate::authorize('view', $plan);

        $cached = $cache->get($context->id(), $plan->public_id);

        if ($cached) {
            return new PlanResource($cached);
        }

        $cache->put($plan);

        return new PlanResource($plan);
    }

    public function update(UpdatePlanRequest $request, Plan $plan, UpdatePlan $action): PlanResource
    {
        Gate::authorize('update', $plan);

        $plan = $action->execute($plan, $request->validated());

        return new PlanResource($plan);
    }

    public function destroy(Plan $plan, ArchivePlan $action): PlanResource
    {
        Gate::authorize('delete', $plan);

        $plan = $action->execute($plan);

        return new PlanResource($plan);
    }
}
