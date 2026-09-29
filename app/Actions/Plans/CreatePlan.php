<?php

declare(strict_types=1);

namespace App\Actions\Plans;

use App\Models\Merchant;
use App\Models\Plan;
use App\Services\PlanCacheService;

final class CreatePlan
{
    public function __construct(
        private readonly PlanCacheService $cache,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Merchant $merchant, array $data): Plan
    {
        $plan = $merchant->plans()->create($data);

        $this->cache->invalidateForMerchant($merchant->id);

        return $plan->refresh();
    }
}
