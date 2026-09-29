<?php

declare(strict_types=1);

namespace Tests\Feature\Plans;

use App\Models\Merchant;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ListPlansTest extends TestCase
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

    public function test_returns_own_merchant_plans(): void
    {
        Plan::factory()->forMerchant($this->merchant)->count(3)->create();

        $response = $this->actingAs($this->owner)->getJson('/api/v1/plans');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_does_not_return_other_merchant_plans(): void
    {
        $otherMerchant = Merchant::factory()->create();
        Plan::factory()->forMerchant($otherMerchant)->count(2)->create();
        Plan::factory()->forMerchant($this->merchant)->count(1)->create();

        $response = $this->actingAs($this->owner)->getJson('/api/v1/plans');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_pagination_works(): void
    {
        Plan::factory()->forMerchant($this->merchant)->count(20)->create();

        $response = $this->actingAs($this->owner)->getJson('/api/v1/plans?page=1');

        $response->assertOk();
        $response->assertJsonCount(15, 'data');
        $response->assertJsonPath('meta.last_page', 2);
        $response->assertJsonPath('meta.total', 20);
    }

    public function test_filter_by_status(): void
    {
        Plan::factory()->forMerchant($this->merchant)->count(2)->create();
        Plan::factory()->forMerchant($this->merchant)->archived()->create();

        $response = $this->actingAs($this->owner)->getJson('/api/v1/plans?status=active');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_member_can_list_plans(): void
    {
        $member = User::factory()->member()->forMerchant($this->merchant)->create();
        Plan::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($member)->getJson('/api/v1/plans');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_user_cannot_list_plans(): void
    {
        $response = $this->getJson('/api/v1/plans');

        $response->assertUnauthorized();
    }
}
