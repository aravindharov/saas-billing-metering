<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DeactivateCustomerTest extends TestCase
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

    public function test_owner_can_deactivate_a_customer(): void
    {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($this->owner)->deleteJson("/api/v1/customers/{$customer->public_id}");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'inactive');
        $this->assertEquals(CustomerStatus::Inactive, $customer->refresh()->status);
    }

    public function test_deactivated_customer_still_exists_in_database(): void
    {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        $this->actingAs($this->owner)->deleteJson("/api/v1/customers/{$customer->public_id}");

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_member_cannot_deactivate_a_customer(): void
    {
        $member = User::factory()->member()->forMerchant($this->merchant)->create();
        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($member)->deleteJson("/api/v1/customers/{$customer->public_id}");

        $response->assertForbidden();
    }

    public function test_cross_tenant_deactivate_returns_404(): void
    {
        $other = Merchant::factory()->create();
        $otherCustomer = Customer::factory()->forMerchant($other)->create();

        $response = $this->actingAs($this->owner)->deleteJson("/api/v1/customers/{$otherCustomer->public_id}");

        $response->assertNotFound();
    }

    public function test_deactivating_already_inactive_customer_stays_inactive(): void
    {
        $customer = Customer::factory()->forMerchant($this->merchant)->inactive()->create();

        $response = $this->actingAs($this->owner)->deleteJson("/api/v1/customers/{$customer->public_id}");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'inactive');
    }
}
