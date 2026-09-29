<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Merchant;
use App\Models\User;
use App\Tenancy\MerchantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchantA;

    private Merchant $merchantB;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchantA = Merchant::factory()->create(['slug' => 'merchant-a']);
        $this->merchantB = Merchant::factory()->create(['slug' => 'merchant-b']);

        $this->userA = User::factory()->owner()->forMerchant($this->merchantA)->create();
        $this->userB = User::factory()->owner()->forMerchant($this->merchantB)->create();
    }

    public function test_user_belongs_to_expected_merchant(): void
    {
        $this->assertTrue($this->userA->merchant->is($this->merchantA));
        $this->assertTrue($this->userB->merchant->is($this->merchantB));
    }

    public function test_user_from_merchant_a_sees_merchant_a_context(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonPath('merchant.slug', 'merchant-a');
    }

    public function test_user_from_merchant_b_sees_merchant_b_context(): void
    {
        Sanctum::actingAs($this->userB);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonPath('merchant.slug', 'merchant-b');
    }

    public function test_merchant_context_is_derived_from_authenticated_user(): void
    {
        Sanctum::actingAs($this->userA);

        $this->getJson('/api/v1/auth/me')->assertOk();

        $context = app(MerchantContext::class);
        $this->assertTrue($context->resolved());
        $this->assertEquals($this->merchantA->id, $context->id());
    }

    public function test_client_supplied_merchant_id_does_not_override_tenant_context(): void
    {
        Sanctum::actingAs($this->userA);

        // Even if the client sends merchant_id in the request body, the
        // server-side context should remain merchant A.
        $response = $this->getJson('/api/v1/auth/me?merchant_id='.$this->merchantB->public_id);

        $response->assertOk();
        $response->assertJsonPath('merchant.slug', 'merchant-a');
    }

    public function test_login_for_merchant_a_does_not_leak_merchant_b_users(): void
    {
        User::factory()->owner()->forMerchant($this->merchantB)->create([
            'email' => 'shared@example.test',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'merchant-a',
            'email' => 'shared@example.test',
            'password' => 'password',
        ]);

        // User exists in merchant B but not merchant A — should fail.
        $response->assertUnauthorized();
    }

    public function test_same_email_can_exist_in_different_merchants(): void
    {
        User::factory()->owner()->forMerchant($this->merchantA)->create([
            'email' => 'shared@example.test',
        ]);
        User::factory()->member()->forMerchant($this->merchantB)->create([
            'email' => 'shared@example.test',
        ]);

        $responseA = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'merchant-a',
            'email' => 'shared@example.test',
            'password' => 'password',
        ]);

        $responseB = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'merchant-b',
            'email' => 'shared@example.test',
            'password' => 'password',
        ]);

        $responseA->assertOk();
        $responseA->assertJsonPath('merchant.slug', 'merchant-a');
        $responseA->assertJsonPath('user.role', 'owner');

        $responseB->assertOk();
        $responseB->assertJsonPath('merchant.slug', 'merchant-b');
        $responseB->assertJsonPath('user.role', 'member');
    }

    public function test_multiple_merchants_can_coexist(): void
    {
        $this->assertDatabaseCount('merchants', 2);
        $this->assertDatabaseHas('merchants', ['slug' => 'merchant-a']);
        $this->assertDatabaseHas('merchants', ['slug' => 'merchant-b']);
    }
}
