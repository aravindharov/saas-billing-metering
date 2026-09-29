<?php

declare(strict_types=1);

namespace Tests\Feature\Plans;

use App\Models\Merchant;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpdatePlanTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
    }

    public function test_owner_can_update_a_plan(): void
    {
        $plan = Plan::factory()->forMerchant($this->merchant)->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->owner)->putJson("/api/v1/plans/{$plan->public_id}", [
            'name' => 'New Name',
            'base_price' => 99900,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'New Name');
        $response->assertJsonPath('data.base_price', 99900);
    }

    public function test_member_cannot_update_a_plan(): void
    {
        $member = User::factory()->member()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($member)->putJson("/api/v1/plans/{$plan->public_id}", [
            'name' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    public function test_cross_tenant_update_returns_404(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherPlan = Plan::factory()->forMerchant($otherMerchant)->create();

        $response = $this->actingAs($this->owner)->putJson("/api/v1/plans/{$otherPlan->public_id}", [
            'name' => 'Hijack',
        ]);

        $response->assertNotFound();
    }

    public function test_validation_rejects_invalid_base_price(): void
    {
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($this->owner)->putJson("/api/v1/plans/{$plan->public_id}", [
            'base_price' => -500,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('base_price');
    }

    public function test_validation_rejects_invalid_billing_cycle(): void
    {
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($this->owner)->putJson("/api/v1/plans/{$plan->public_id}", [
            'billing_cycle' => 'quarterly',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('billing_cycle');
    }

    public function test_duplicate_name_within_merchant_is_rejected(): void
    {
        Plan::factory()->forMerchant($this->merchant)->create(['name' => 'Existing']);
        $plan = Plan::factory()->forMerchant($this->merchant)->create(['name' => 'Other']);

        $response = $this->actingAs($this->owner)->putJson("/api/v1/plans/{$plan->public_id}", [
            'name' => 'Existing',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
    }

    public function test_plan_can_keep_own_name_on_update(): void
    {
        $plan = Plan::factory()->forMerchant($this->merchant)->create(['name' => 'Keep Me']);

        $response = $this->actingAs($this->owner)->putJson("/api/v1/plans/{$plan->public_id}", [
            'name' => 'Keep Me',
            'base_price' => 12300,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.base_price', 12300);
    }

    public function test_client_cannot_override_merchant_id_on_update(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($this->owner)->putJson("/api/v1/plans/{$plan->public_id}", [
            'name' => 'Safe',
            'merchant_id' => $otherMerchant->id,
        ]);

        $response->assertOk();
        $this->assertEquals($this->merchant->id, $plan->refresh()->merchant_id);
    }
}
