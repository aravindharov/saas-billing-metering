<?php

declare(strict_types=1);

namespace Tests\Feature\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CancelSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private User $member;

    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $this->member = User::factory()->member()->forMerchant($this->merchant)->create();

        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $this->subscription = Subscription::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($customer)
            ->forPlan($plan)
            ->create();
    }

    public function test_owner_can_cancel_subscription(): void
    {
        $response = $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/cancel"
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'cancelled');
        $response->assertJsonPath('data.cancelled_at', fn ($v) => $v !== null);
    }

    public function test_cancelled_subscription_has_timestamp(): void
    {
        $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/cancel"
        )->assertOk();

        $this->subscription->refresh();
        $this->assertSame(SubscriptionStatus::Cancelled, $this->subscription->status);
        $this->assertNotNull($this->subscription->cancelled_at);
    }

    public function test_member_cannot_cancel_subscription(): void
    {
        $response = $this->actingAs($this->member)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/cancel"
        );

        $response->assertForbidden();
    }

    public function test_cannot_cancel_already_cancelled_subscription(): void
    {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $cancelled = Subscription::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($customer)
            ->forPlan($plan)
            ->cancelled()
            ->create();

        $response = $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$cancelled->public_id}/cancel"
        );

        $response->assertUnprocessable();
    }

    public function test_cannot_cancel_subscription_from_another_merchant(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherCustomer = Customer::factory()->forMerchant($otherMerchant)->create();
        $otherPlan = Plan::factory()->forMerchant($otherMerchant)->create();
        $otherSub = Subscription::factory()
            ->forMerchant($otherMerchant)
            ->forCustomer($otherCustomer)
            ->forPlan($otherPlan)
            ->create();

        $response = $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$otherSub->public_id}/cancel"
        );

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_cancel(): void
    {
        $response = $this->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/cancel"
        );

        $response->assertUnauthorized();
    }
}
