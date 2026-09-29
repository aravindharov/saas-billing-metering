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

final class UsageImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private UsageEvent $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $subscription = Subscription::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($customer)
            ->forPlan($plan)
            ->create();
        $this->event = UsageEvent::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($customer)
            ->forSubscription($subscription)
            ->create();
    }

    public function test_no_put_endpoint_exists(): void
    {
        $response = $this->actingAs($this->owner)
            ->putJson("/api/v1/usage/{$this->event->public_id}", [
                'quantity' => 999,
            ]);

        $response->assertStatus(405);
    }

    public function test_no_patch_endpoint_exists(): void
    {
        $response = $this->actingAs($this->owner)
            ->patchJson("/api/v1/usage/{$this->event->public_id}", [
                'quantity' => 999,
            ]);

        $response->assertStatus(405);
    }

    public function test_no_delete_endpoint_exists(): void
    {
        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/v1/usage/{$this->event->public_id}");

        $response->assertStatus(405);
    }
}
