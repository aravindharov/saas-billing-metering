<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShowCustomerTest extends TestCase
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

    public function test_owner_can_view_own_customer(): void
    {
        $customer = Customer::factory()->forMerchant($this->merchant)->create(['name' => 'John']);

        $response = $this->actingAs($this->owner)->getJson("/api/v1/customers/{$customer->public_id}");

        $response->assertOk();
        $response->assertJsonPath('data.name', 'John');
        $response->assertJsonPath('data.id', $customer->public_id);
    }

    public function test_member_can_view_own_customer(): void
    {
        $member = User::factory()->member()->forMerchant($this->merchant)->create();
        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($member)->getJson("/api/v1/customers/{$customer->public_id}");

        $response->assertOk();
    }

    public function test_cross_tenant_customer_returns_404(): void
    {
        $other = Merchant::factory()->create();
        $otherCustomer = Customer::factory()->forMerchant($other)->create();

        $response = $this->actingAs($this->owner)->getJson("/api/v1/customers/{$otherCustomer->public_id}");

        $response->assertNotFound();
    }

    public function test_nonexistent_customer_returns_404(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/customers/01NONEXISTENT000000000000');

        $response->assertNotFound();
    }
}
