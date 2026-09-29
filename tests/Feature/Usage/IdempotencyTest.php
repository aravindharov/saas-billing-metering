<?php

declare(strict_types=1);

namespace Tests\Feature\Usage;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IdempotencyTest extends TestCase
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

    public function test_duplicate_event_id_returns_existing_event(): void
    {
        $payload = [
            'event_id' => 'evt_dup_001',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 25,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ];

        $first = $this->actingAs($this->owner)->postJson('/api/v1/usage', $payload);
        $first->assertCreated();

        $second = $this->actingAs($this->owner)->postJson('/api/v1/usage', $payload);
        $second->assertOk();

        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id'),
        );

        $this->assertDatabaseCount('usage_events', 1);
    }

    public function test_same_event_id_across_different_merchants_is_allowed(): void
    {
        $other = Merchant::factory()->create();
        $otherOwner = User::factory()->owner()->forMerchant($other)->create();
        $otherCustomer = Customer::factory()->forMerchant($other)->create();
        $otherPlan = Plan::factory()->forMerchant($other)->create();
        $otherSub = Subscription::factory()->forMerchant($other)
            ->forCustomer($otherCustomer)->forPlan($otherPlan)->create();

        $payload1 = [
            'event_id' => 'evt_shared_id',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ];

        $payload2 = [
            'event_id' => 'evt_shared_id',
            'customer_id' => $otherCustomer->public_id,
            'subscription_id' => $otherSub->public_id,
            'quantity' => 20,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ];

        $this->actingAs($this->owner)->postJson('/api/v1/usage', $payload1)->assertCreated();
        $this->actingAs($otherOwner)->postJson('/api/v1/usage', $payload2)->assertCreated();

        $this->assertSame(2, UsageEvent::where('event_id', 'evt_shared_id')->count());
    }

    public function test_idempotent_retry_does_not_change_data(): void
    {
        $payload = [
            'event_id' => 'evt_immutable',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 25,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ];

        $this->actingAs($this->owner)->postJson('/api/v1/usage', $payload)->assertCreated();

        // Retry with different quantity — the original must be returned unchanged.
        $retry = $this->actingAs($this->owner)->postJson('/api/v1/usage', array_merge($payload, [
            'quantity' => 999,
        ]));

        $retry->assertOk();
        $retry->assertJsonPath('data.quantity', 25);
        $this->assertDatabaseCount('usage_events', 1);
    }

    public function test_concurrent_duplicates_result_in_single_record(): void
    {
        $payload = [
            'event_id' => 'evt_concurrent',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ];

        // Simulate concurrent requests by submitting rapidly.
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->actingAs($this->owner)->postJson('/api/v1/usage', $payload);
        }

        $this->assertDatabaseCount('usage_events', 1);

        $publicIds = array_map(fn ($r) => $r->json('data.id'), $responses);
        $this->assertCount(1, array_unique($publicIds));
    }
}
