<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cross-tenant access must not leak data (404 on scoped route models).
 */
final class TenantResourceIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchantA;

    private Merchant $merchantB;

    private User $userA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchantA = Merchant::factory()->create(['slug' => 'tenant-a']);
        $this->merchantB = Merchant::factory()->create(['slug' => 'tenant-b']);
        $this->userA = User::factory()->owner()->forMerchant($this->merchantA)->create();
    }

    public function test_cannot_show_other_merchant_plan(): void
    {
        $planB = Plan::factory()->forMerchant($this->merchantB)->create();

        $this->actingAs($this->userA)
            ->getJson('/api/v1/plans/'.$planB->public_id)
            ->assertNotFound();
    }

    public function test_cannot_show_other_merchant_customer(): void
    {
        $customerB = Customer::factory()->forMerchant($this->merchantB)->create();

        $this->actingAs($this->userA)
            ->getJson('/api/v1/customers/'.$customerB->public_id)
            ->assertNotFound();
    }

    public function test_cannot_show_other_merchant_subscription(): void
    {
        $subscriptionB = Subscription::factory()->forMerchant($this->merchantB)->create();

        $this->actingAs($this->userA)
            ->getJson('/api/v1/subscriptions/'.$subscriptionB->public_id)
            ->assertNotFound();
    }

    public function test_cannot_show_other_merchant_invoice(): void
    {
        $customerB = Customer::factory()->forMerchant($this->merchantB)->create();
        $subscriptionB = Subscription::factory()->forMerchant($this->merchantB)->forCustomer($customerB)->create();
        $invoiceB = Invoice::factory()->forSubscription($subscriptionB)->create();

        $this->actingAs($this->userA)
            ->getJson('/api/v1/invoices/'.$invoiceB->public_id)
            ->assertNotFound();
    }

    public function test_cannot_access_other_merchant_dashboard(): void
    {
        $this->actingAs($this->userA)
            ->getJson('/api/v1/merchants/'.$this->merchantB->public_id.'/dashboard')
            ->assertNotFound();
    }

    public function test_daily_usage_filter_with_other_merchant_customer_returns_empty(): void
    {
        $customerB = Customer::factory()->forMerchant($this->merchantB)->create();

        $response = $this->actingAs($this->userA)->getJson(
            '/api/v1/usage/daily?customer_id='.$customerB->public_id,
        );

        $response->assertOk();
        $this->assertSame([], $response->json('data'));
    }

    public function test_cannot_cancel_other_merchant_subscription(): void
    {
        $subscriptionB = Subscription::factory()->forMerchant($this->merchantB)->create();

        $this->actingAs($this->userA)
            ->postJson('/api/v1/subscriptions/'.$subscriptionB->public_id.'/cancel')
            ->assertNotFound();
    }

    public function test_cannot_generate_invoice_for_other_merchant_subscription(): void
    {
        $subscriptionB = Subscription::factory()->forMerchant($this->merchantB)->create();

        $this->actingAs($this->userA)
            ->postJson('/api/v1/subscriptions/'.$subscriptionB->public_id.'/generate-invoice')
            ->assertNotFound();
    }
}
