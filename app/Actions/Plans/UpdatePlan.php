<?php

declare(strict_types=1);

namespace App\Actions\Plans;

use App\Models\Plan;
use App\Services\PlanCacheService;

final class UpdatePlan
{
    public function __construct(
        private readonly PlanCacheService $cache,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Plan $plan, array $data): Plan
    {
        $plan->update($data);

        $this->cache->invalidatePlan($plan);

        return $plan->refresh();
    }
}
