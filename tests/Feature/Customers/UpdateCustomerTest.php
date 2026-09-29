<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpdateCustomerTest extends TestCase
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

    public function test_owner_can_update_a_customer(): void
    {
        $customer = Customer::factory()->forMerchant($this->merchant)->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->owner)->putJson("/api/v1/customers/{$customer->public_id}", [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'New Name');
        $response->assertJsonPath('data.email', 'new@example.com');
    }

    public function test_member_cannot_update_a_customer(): void
    {
        $member = User::factory()->member()->forMerchant($this->merchant)->create();
        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($member)->putJson("/api/v1/customers/{$customer->public_id}", [
            'name' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    public function test_cross_tenant_update_returns_404(): void
    {
        $other = Merchant::factory()->create();
        $otherCustomer = Customer::factory()->forMerchant($other)->create();

        $response = $this->actingAs($this->owner)->putJson("/api/v1/customers/{$otherCustomer->public_id}", [
            'name' => 'Hijack',
        ]);

        $response->assertNotFound();
    }

    public function test_validation_rejects_invalid_email(): void
    {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($this->owner)->putJson("/api/v1/customers/{$customer->public_id}", [
            'email' => 'not-valid',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function test_duplicate_external_reference_within_merchant_is_rejected(): void
    {
        Customer::factory()->forMerchant($this->merchant)->withExternalReference('CRM-001')->create();
        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($this->owner)->putJson("/api/v1/customers/{$customer->public_id}", [
            'external_reference' => 'CRM-001',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('external_reference');
    }

    public function test_customer_can_keep_own_external_reference_on_update(): void
    {
        $customer = Customer::factory()->forMerchant($this->merchant)
            ->withExternalReference('CRM-MINE')
            ->create();

        $response = $this->actingAs($this->owner)->putJson("/api/v1/customers/{$customer->public_id}", [
            'external_reference' => 'CRM-MINE',
            'name' => 'Updated Name',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_client_cannot_override_merchant_id_on_update(): void
    {
        $other = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($this->owner)->putJson("/api/v1/customers/{$customer->public_id}", [
            'name' => 'Safe',
            'merchant_id' => $other->id,
        ]);

        $response->assertOk();
        $this->assertEquals($this->merchant->id, $customer->refresh()->merchant_id);
    }
}
