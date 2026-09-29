<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ListCustomersTest extends TestCase
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

    public function test_returns_own_merchant_customers(): void
    {
        Customer::factory()->forMerchant($this->merchant)->count(3)->create();

        $response = $this->actingAs($this->owner)->getJson('/api/v1/customers');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_does_not_return_other_merchant_customers(): void
    {
        $other = Merchant::factory()->create();
        Customer::factory()->forMerchant($other)->count(2)->create();
        Customer::factory()->forMerchant($this->merchant)->count(1)->create();

        $response = $this->actingAs($this->owner)->getJson('/api/v1/customers');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_pagination_works(): void
    {
        Customer::factory()->forMerchant($this->merchant)->count(20)->create();

        $response = $this->actingAs($this->owner)->getJson('/api/v1/customers?page=1');

        $response->assertOk();
        $response->assertJsonCount(15, 'data');
        $response->assertJsonPath('meta.last_page', 2);
        $response->assertJsonPath('meta.total', 20);
    }

    public function test_filter_by_status(): void
    {
        Customer::factory()->forMerchant($this->merchant)->count(2)->create();
        Customer::factory()->forMerchant($this->merchant)->inactive()->create();

        $response = $this->actingAs($this->owner)->getJson('/api/v1/customers?status=active');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_search_by_name(): void
    {
        Customer::factory()->forMerchant($this->merchant)->create(['name' => 'John Smith']);
        Customer::factory()->forMerchant($this->merchant)->create(['name' => 'Jane Doe']);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/customers?search=John');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'John Smith');
    }

    public function test_search_by_email(): void
    {
        Customer::factory()->forMerchant($this->merchant)->create(['email' => 'john@example.com']);
        Customer::factory()->forMerchant($this->merchant)->create(['email' => 'jane@example.com']);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/customers?search=john@');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_search_by_external_reference(): void
    {
        Customer::factory()->forMerchant($this->merchant)->withExternalReference('CRM-100')->create();
        Customer::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($this->owner)->getJson('/api/v1/customers?search=CRM-100');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_member_can_list_customers(): void
    {
        $member = User::factory()->member()->forMerchant($this->merchant)->create();
        Customer::factory()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($member)->getJson('/api/v1/customers');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_user_cannot_list_customers(): void
    {
        $response = $this->getJson('/api/v1/customers');

        $response->assertUnauthorized();
    }
}
