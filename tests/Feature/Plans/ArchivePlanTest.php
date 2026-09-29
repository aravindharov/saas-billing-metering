<?php

declare(strict_types=1);

namespace Tests\Feature\Plans;

use App\Enums\PlanStatus;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ArchivePlanTest extends TestCase
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

    public function test_owner_can_archive_a_plan(): void
    {
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($this->owner)->deleteJson("/api/v1/plans/{$plan->public_id}");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'archived');

        $this->assertEquals(PlanStatus::Archived, $plan->refresh()->status);
    }

    public function test_archived_plan_still_exists_in_database(): void
    {
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        $this->actingAs($this->owner)->deleteJson("/api/v1/plans/{$plan->public_id}");

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    public function test_member_cannot_archive_a_plan(): void
    {
        $member = User::factory()->member()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($member)->deleteJson("/api/v1/plans/{$plan->public_id}");

        $response->assertForbidden();
    }

    public function test_cross_tenant_archive_returns_404(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherPlan = Plan::factory()->forMerchant($otherMerchant)->create();

        $response = $this->actingAs($this->owner)->deleteJson("/api/v1/plans/{$otherPlan->public_id}");

        $response->assertNotFound();
    }

    public function test_archiving_already_archived_plan_stays_archived(): void
    {
        $plan = Plan::factory()->forMerchant($this->merchant)->archived()->create();

        $response = $this->actingAs($this->owner)->deleteJson("/api/v1/plans/{$plan->public_id}");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'archived');
    }
}
