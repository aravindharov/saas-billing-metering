<?php

declare(strict_types=1);

namespace Tests\Feature\Plans;

use App\Enums\BillingCycle;
use App\Enums\PlanStatus;
use App\Models\Merchant;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlanModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_belongs_to_merchant(): void
    {
        $merchant = Merchant::factory()->create();
        $plan = Plan::factory()->forMerchant($merchant)->create();

        $this->assertEquals($merchant->id, $plan->merchant->id);
    }

    public function test_merchant_has_many_plans(): void
    {
        $merchant = Merchant::factory()->create();
        Plan::factory()->forMerchant($merchant)->count(3)->create();

        $this->assertCount(3, $merchant->plans);
    }

    public function test_billing_cycle_is_cast_to_enum(): void
    {
        $plan = Plan::factory()->monthly()->create();

        $this->assertInstanceOf(BillingCycle::class, $plan->billing_cycle);
        $this->assertSame(BillingCycle::Monthly, $plan->billing_cycle);
    }

    public function test_status_is_cast_to_enum(): void
    {
        $plan = Plan::factory()->create();

        $this->assertInstanceOf(PlanStatus::class, $plan->status);
        $this->assertSame(PlanStatus::Active, $plan->status);
    }

    public function test_base_price_is_cast_to_integer(): void
    {
        $plan = Plan::factory()->create(['base_price' => 49900]);

        $this->assertIsInt($plan->base_price);
        $this->assertSame(49900, $plan->base_price);
    }

    public function test_public_id_is_auto_generated(): void
    {
        $plan = Plan::factory()->create();

        $this->assertNotEmpty($plan->public_id);
        $this->assertSame(26, strlen($plan->public_id));
    }

    public function test_route_key_is_public_id(): void
    {
        $plan = new Plan;

        $this->assertSame('public_id', $plan->getRouteKeyName());
    }

    public function test_is_active_returns_correctly(): void
    {
        $active = Plan::factory()->create();
        $archived = Plan::factory()->archived()->create();

        $this->assertTrue($active->isActive());
        $this->assertFalse($archived->isActive());
    }

    public function test_is_archived_returns_correctly(): void
    {
        $active = Plan::factory()->create();
        $archived = Plan::factory()->archived()->create();

        $this->assertFalse($active->isArchived());
        $this->assertTrue($archived->isArchived());
    }
}
