<?php

declare(strict_types=1);

namespace Tests\Feature\Plans;

use App\Enums\BillingCycle;
use App\Enums\PlanStatus;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreatePlanTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $this->member = User::factory()->member()->forMerchant($this->merchant)->create();
    }

    public function test_owner_can_create_a_plan(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'name' => 'Professional',
            'base_price' => 49900,
            'billing_cycle' => 'monthly',
            'included_usage_units' => 10000,
            'overage_rate' => 5,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Professional');
        $response->assertJsonPath('data.base_price', 49900);
        $response->assertJsonPath('data.billing_cycle', 'monthly');
        $response->assertJsonPath('data.included_usage_units', 10000);
        $response->assertJsonPath('data.overage_rate', 5);
        $response->assertJsonPath('data.status', 'active');
        $response->assertJsonStructure(['data' => ['id', 'created_at', 'updated_at']]);

        $this->assertDatabaseHas('plans', [
            'merchant_id' => $this->merchant->id,
            'name' => 'Professional',
            'base_price' => 49900,
            'billing_cycle' => BillingCycle::Monthly->value,
            'status' => PlanStatus::Active->value,
        ]);
    }

    public function test_member_cannot_create_a_plan(): void
    {
        $response = $this->actingAs($this->member)->postJson('/api/v1/plans', [
            'name' => 'Professional',
            'base_price' => 49900,
            'billing_cycle' => 'monthly',
            'included_usage_units' => 10000,
            'overage_rate' => 5,
        ]);

        $response->assertForbidden();
    }

    public function test_name_is_required(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'base_price' => 49900,
            'billing_cycle' => 'monthly',
            'included_usage_units' => 10000,
            'overage_rate' => 5,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
    }

    public function test_name_must_be_unique_within_merchant(): void
    {
        Plan::factory()->forMerchant($this->merchant)->create(['name' => 'Starter']);

        $response = $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'name' => 'Starter',
            'base_price' => 9900,
            'billing_cycle' => 'monthly',
            'included_usage_units' => 1000,
            'overage_rate' => 5,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
    }

    public function test_same_name_allowed_for_different_merchants(): void
    {
        $otherMerchant = Merchant::factory()->create();
        Plan::factory()->forMerchant($otherMerchant)->create(['name' => 'Starter']);

        $response = $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'name' => 'Starter',
            'base_price' => 9900,
            'billing_cycle' => 'monthly',
            'included_usage_units' => 1000,
            'overage_rate' => 5,
        ]);

        $response->assertCreated();
    }

    public function test_base_price_must_be_non_negative_integer(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'name' => 'Test',
            'base_price' => -100,
            'billing_cycle' => 'monthly',
            'included_usage_units' => 1000,
            'overage_rate' => 5,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('base_price');
    }

    public function test_billing_cycle_must_be_valid_enum(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'name' => 'Test',
            'base_price' => 9900,
            'billing_cycle' => 'weekly',
            'included_usage_units' => 1000,
            'overage_rate' => 5,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('billing_cycle');
    }

    public function test_included_usage_units_must_be_non_negative(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'name' => 'Test',
            'base_price' => 9900,
            'billing_cycle' => 'monthly',
            'included_usage_units' => -1,
            'overage_rate' => 5,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('included_usage_units');
    }

    public function test_overage_rate_must_be_non_negative(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'name' => 'Test',
            'base_price' => 9900,
            'billing_cycle' => 'monthly',
            'included_usage_units' => 1000,
            'overage_rate' => -1,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('overage_rate');
    }

    public function test_client_cannot_set_merchant_id(): void
    {
        $otherMerchant = Merchant::factory()->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'name' => 'Hijack',
            'base_price' => 9900,
            'billing_cycle' => 'monthly',
            'included_usage_units' => 1000,
            'overage_rate' => 5,
            'merchant_id' => $otherMerchant->id,
        ]);

        $response->assertCreated();

        $plan = Plan::where('name', 'Hijack')->firstOrFail();
        $this->assertEquals($this->merchant->id, $plan->merchant_id);
    }

    public function test_client_cannot_set_status_on_create(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'name' => 'Sneaky',
            'base_price' => 9900,
            'billing_cycle' => 'monthly',
            'included_usage_units' => 1000,
            'overage_rate' => 5,
            'status' => 'archived',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'active');
    }

    public function test_unauthenticated_user_cannot_create_plan(): void
    {
        $response = $this->postJson('/api/v1/plans', [
            'name' => 'Professional',
            'base_price' => 49900,
            'billing_cycle' => 'monthly',
            'included_usage_units' => 10000,
            'overage_rate' => 5,
        ]);

        $response->assertUnauthorized();
    }

    public function test_plan_gets_yearly_billing_cycle(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'name' => 'Annual',
            'base_price' => 499900,
            'billing_cycle' => 'yearly',
            'included_usage_units' => 100000,
            'overage_rate' => 2,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.billing_cycle', 'yearly');
    }

    public function test_internal_ids_are_not_exposed(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/plans', [
            'name' => 'Test',
            'base_price' => 9900,
            'billing_cycle' => 'monthly',
            'included_usage_units' => 1000,
            'overage_rate' => 5,
        ]);

        $response->assertCreated();
        $data = $response->json('data');
        $this->assertArrayNotHasKey('merchant_id', $data);
        $this->assertStringStartsWith('0', $data['id']); // ULID starts with 0 in 2020s
    }
}
