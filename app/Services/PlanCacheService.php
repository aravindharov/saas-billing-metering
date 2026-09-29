<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Facades\Cache;

/**
 * Caches plan lookups scoped by merchant.
 *
 * Key structure:
 *   Single plan:  plans:{merchant_id}:{plan_public_id}
 *   Plan list:    plans:{merchant_id}:list
 *
 * Invalidation triggers:
 *   - Plan created  → flush list cache for the merchant
 *   - Plan updated  → flush the individual plan + list cache
 *   - Plan archived → flush the individual plan + list cache
 *
 * Merchant isolation:
 *   Cache keys include the merchant_id, so Merchant A can never
 *   receive cached data belonging to Merchant B.
 *
 * TTL: 1 hour (3600 seconds). Explicit invalidation on writes
 * ensures consistency. The TTL serves as a safety net.
 */
final class PlanCacheService
{
    private const int TTL = 3600;

    public function get(int $merchantId, string $publicId): ?Plan
    {
        $key = $this->planKey($merchantId, $publicId);

        return Cache::get($key);
    }

    public function put(Plan $plan): void
    {
        $key = $this->planKey($plan->merchant_id, $plan->public_id);

        Cache::put($key, $plan, self::TTL);
    }

    public function invalidateForMerchant(int $merchantId): void
    {
        Cache::forget($this->listKey($merchantId));
    }

    public function invalidatePlan(Plan $plan): void
    {
        Cache::forget($this->planKey($plan->merchant_id, $plan->public_id));
        Cache::forget($this->listKey($plan->merchant_id));
    }

    public function planKey(int $merchantId, string $publicId): string
    {
        return "plans:{$merchantId}:{$publicId}";
    }

    public function listKey(int $merchantId): string
    {
        return "plans:{$merchantId}:list";
    }
}
