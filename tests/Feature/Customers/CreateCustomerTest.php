<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateCustomerTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $this->member = User::factory()->member()->forMerchant($this->merchant)->create();
    }

    public function test_owner_can_create_a_customer(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/customers', [
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'external_reference' => 'CRM-10001',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'John Smith');
        $response->assertJsonPath('data.email', 'john@example.com');
        $response->assertJsonPath('data.external_reference', 'CRM-10001');
        $response->assertJsonPath('data.status', 'active');
        $response->assertJsonStructure(['data' => ['id', 'created_at', 'updated_at']]);

        $this->assertDatabaseHas('customers', [
            'merchant_id' => $this->merchant->id,
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'status' => CustomerStatus::Active->value,
        ]);
    }

    public function test_member_cannot_create_a_customer(): void
    {
        $response = $this->actingAs($this->member)->postJson('/api/v1/customers', [
            'name' => 'John Smith',
            'email' => 'john@example.com',
        ]);

        $response->assertForbidden();
    }

    public function test_name_is_required(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/customers', [
            'email' => 'john@example.com',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
    }

    public function test_email_is_required(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/customers', [
            'name' => 'John Smith',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function test_email_must_be_valid(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/customers', [
            'name' => 'John Smith',
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function test_name_must_not_exceed_max_length(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/customers', [
            'name' => str_repeat('a', 256),
            'email' => 'john@example.com',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
    }

    public function test_external_reference_is_optional(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/customers', [
            'name' => 'John Smith',
            'email' => 'john@example.com',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.external_reference', null);
    }

    public function test_external_reference_must_be_unique_within_merchant(): void
    {
        Customer::factory()->forMerchant($this->merchant)->withExternalReference('CRM-001')->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/customers', [
            'name' => 'Another',
            'email' => 'another@example.com',
            'external_reference' => 'CRM-001',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('external_reference');
    }

    public function test_same_external_reference_allowed_for_different_merchants(): void
    {
        $other = Merchant::factory()->create();
        Customer::factory()->forMerchant($other)->withExternalReference('CRM-001')->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/customers', [
            'name' => 'Mine',
            'email' => 'mine@example.com',
            'external_reference' => 'CRM-001',
        ]);

        $response->assertCreated();
    }

    public function test_client_cannot_set_merchant_id(): void
    {
        $other = Merchant::factory()->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/customers', [
            'name' => 'Hijack',
            'email' => 'hijack@example.com',
            'merchant_id' => $other->id,
        ]);

        $response->assertCreated();

        $customer = Customer::where('email', 'hijack@example.com')->firstOrFail();
        $this->assertEquals($this->merchant->id, $customer->merchant_id);
    }

    public function test_client_cannot_set_status_on_create(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/customers', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'status' => 'inactive',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'active');
    }

    public function test_unauthenticated_user_cannot_create_customer(): void
    {
        $response = $this->postJson('/api/v1/customers', [
            'name' => 'John Smith',
            'email' => 'john@example.com',
        ]);

        $response->assertUnauthorized();
    }

    public function test_internal_ids_are_not_exposed(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/customers', [
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);

        $response->assertCreated();
        $data = $response->json('data');
        $this->assertArrayNotHasKey('merchant_id', $data);
        $this->assertSame(26, strlen($data['id']));
    }
}
