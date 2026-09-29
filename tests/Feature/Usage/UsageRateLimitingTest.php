<?php

declare(strict_types=1);

namespace Tests\Feature\Usage;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class UsageRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private Customer $customer;

    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $this->customer = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $this->subscription = Subscription::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forPlan($plan)
            ->create();
    }

    public function test_requests_within_limit_are_accepted(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_rate_ok',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 1,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertCreated();
    }

    public function test_exceeding_rate_limit_returns_429(): void
    {
        // Temporarily lower the limit to 2 for testability.
        RateLimiter::for('usage-ingest', function () {
            return Limit::perMinute(2)->by('usage-ingest:'.$this->merchant->id);
        });

        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($this->owner)->postJson('/api/v1/usage', [
                'event_id' => 'evt_rl_'.$i,
                'customer_id' => $this->customer->public_id,
                'subscription_id' => $this->subscription->public_id,
                'quantity' => 1,
                'occurred_at' => '2026-09-29T14:30:00Z',
            ])->assertCreated();
        }

        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_rl_over',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 1,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertStatus(429);
    }

    public function test_one_merchant_limit_does_not_affect_another(): void
    {
        // Lower limit for this test.
        RateLimiter::for('usage-ingest', function ($request) {
            $user = $request->user();
            $key = $user !== null ? $user->merchant_id : 'anonymous';

            return Limit::perMinute(1)->by('usage-ingest:'.$key);
        });

        // Exhaust merchant A's limit.
        $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_exhaust',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 1,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ])->assertCreated();

        // Merchant A is now rate limited.
        $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_over_limit',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 1,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ])->assertStatus(429);

        // Merchant B should be unaffected.
        $other = Merchant::factory()->create();
        $otherOwner = User::factory()->owner()->forMerchant($other)->create();
        $otherCustomer = Customer::factory()->forMerchant($other)->create();
        $otherPlan = Plan::factory()->forMerchant($other)->create();
        $otherSub = Subscription::factory()->forMerchant($other)
            ->forCustomer($otherCustomer)->forPlan($otherPlan)->create();

        $response = $this->actingAs($otherOwner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_other_merchant',
            'customer_id' => $otherCustomer->public_id,
            'subscription_id' => $otherSub->public_id,
            'quantity' => 1,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertCreated();
    }
}
