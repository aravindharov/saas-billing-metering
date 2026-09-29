<?php

declare(strict_types=1);

namespace App\Actions\Plans;

use App\Enums\PlanStatus;
use App\Models\Plan;
use App\Services\PlanCacheService;

final class ArchivePlan
{
    public function __construct(
        private readonly PlanCacheService $cache,
    ) {}

    public function execute(Plan $plan): Plan
    {
        $plan->update(['status' => PlanStatus::Archived]);

        $this->cache->invalidatePlan($plan);

        return $plan->refresh();
    }
}
