<?php

declare(strict_types=1);

namespace Tests\Feature\Plans;

use App\Models\Merchant;
use App\Models\Plan;
use App\Models\User;
use App\Services\PlanCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class PlanCacheTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private PlanCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $this->cacheService = new PlanCacheService;
    }

    public function test_show_caches_plan_lookup(): void
    {
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        $this->actingAs($this->owner)->getJson("/api/v1/plans/{$plan->public_id}");

        $key = $this->cacheService->planKey($this->merchant->id, $plan->public_id);
        $this->assertTrue(Cache::has($key));
    }

    public function test_update_invalidates_plan_cache(): void
    {
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        // Warm the cache
        $this->actingAs($this->owner)->getJson("/api/v1/plans/{$plan->public_id}");

        $key = $this->cacheService->planKey($this->merchant->id, $plan->public_id);
        $this->assertTrue(Cache::has($key));

        // Update invalidates
        $this->actingAs($this->owner)->putJson("/api/v1/plans/{$plan->public_id}", [
            'name' => 'Updated',
        ]);

        $this->assertFalse(Cache::has($key));
    }

    public function test_archive_invalidates_plan_cache(): void
    {
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        // Warm the cache
        $this->actingAs($this->owner)->getJson("/api/v1/plans/{$plan->public_id}");

        $key = $this->cacheService->planKey($this->merchant->id, $plan->public_id);
        $this->assertTrue(Cache::has($key));

        // Archive invalidates
        $this->actingAs($this->owner)->deleteJson("/api/v1/plans/{$plan->public_id}");

        $this->assertFalse(Cache::has($key));
    }

    public function test_create_invalidates_list_cache(): void
    {
        $listKey = $this->cacheService->listKey($this->merchant->id);
        Cache::put($listKey, 'cached-list', 3600);

        $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'name' => 'New Plan',
            'base_price' => 9900,
            'billing_cycle' => 'monthly',
            'included_usage_units' => 1000,
            'overage_rate' => 5,
        ]);

        $this->assertFalse(Cache::has($listKey));
    }

    public function test_merchant_cache_isolation(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherPlan = Plan::factory()->forMerchant($otherMerchant)->create();

        $merchantAKey = $this->cacheService->planKey($this->merchant->id, 'some-plan');
        $merchantBKey = $this->cacheService->planKey($otherMerchant->id, $otherPlan->public_id);

        $this->assertNotEquals($merchantAKey, $merchantBKey);
        $this->assertStringContainsString((string) $this->merchant->id, $merchantAKey);
        $this->assertStringContainsString((string) $otherMerchant->id, $merchantBKey);
    }
}
