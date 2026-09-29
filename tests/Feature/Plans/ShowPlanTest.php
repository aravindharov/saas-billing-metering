<?php

declare(strict_types=1);

namespace Tests\Feature\Plans;

use App\Models\Merchant;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShowPlanTest extends TestCase
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

    public function test_owner_can_view_own_plan(): void
    {
        $plan = Plan::factory()->forMerchant($this->merchant)->create(['name' => 'Starter']);

        $response = $this->actingAs($this->owner)->getJson("/api/v1/plans/{$plan->public_id}");

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Starter');
        $response->assertJsonPath('data.id', $plan->public_id);
    }

    public function test_member_can_view_own_plan(): void
    {
        $member = User::factory()->member()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($member)->getJson("/api/v1/plans/{$plan->public_id}");

        $response->assertOk();
    }

    public function test_cross_tenant_plan_returns_404(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherPlan = Plan::factory()->forMerchant($otherMerchant)->create();

        $response = $this->actingAs($this->owner)->getJson("/api/v1/plans/{$otherPlan->public_id}");

        $response->assertNotFound();
    }

    public function test_nonexistent_plan_returns_404(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/plans/01NONEXISTENT000000000000');

        $response->assertNotFound();
    }
}
