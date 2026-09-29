<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create(['slug' => 'acme']);
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create([
            'email' => 'owner@acme.test',
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'acme',
            'email' => 'owner@acme.test',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'role'],
            'merchant' => ['id', 'name', 'slug'],
            'token',
        ]);
    }

    public function test_login_returns_correct_user_and_merchant(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'acme',
            'email' => 'owner@acme.test',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.email', 'owner@acme.test');
        $response->assertJsonPath('user.role', 'owner');
        $response->assertJsonPath('merchant.slug', 'acme');
    }

    public function test_invalid_password_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'acme',
            'email' => 'owner@acme.test',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'The provided credentials are incorrect.');
    }

    public function test_unknown_merchant_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'nonexistent',
            'email' => 'owner@acme.test',
            'password' => 'password',
        ]);

        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'The provided credentials are incorrect.');
    }

    public function test_unknown_user_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'acme',
            'email' => 'nobody@acme.test',
            'password' => 'password',
        ]);

        $response->assertUnauthorized();
        $response->assertJsonPath('message', 'The provided credentials are incorrect.');
    }

    public function test_login_validation_requires_all_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['merchant', 'email', 'password']);
    }

    public function test_login_validation_requires_valid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'acme',
            'email' => 'not-an-email',
            'password' => 'password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_password_is_never_returned_in_response(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'acme',
            'email' => 'owner@acme.test',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonMissingPath('user.password');
    }

    public function test_token_is_issued_on_successful_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'acme',
            'email' => 'owner@acme.test',
            'password' => 'password',
        ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->owner->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_suspended_merchant_cannot_login(): void
    {
        $suspended = Merchant::factory()->suspended()->create(['slug' => 'suspended-co']);
        User::factory()->owner()->forMerchant($suspended)->create([
            'email' => 'owner@suspended.test',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'suspended-co',
            'email' => 'owner@suspended.test',
            'password' => 'password',
        ]);

        $response->assertUnauthorized();
    }

    public function test_sequential_ids_are_not_exposed(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'acme',
            'email' => 'owner@acme.test',
            'password' => 'password',
        ]);

        $response->assertOk();

        $userId = $response->json('user.id');
        $merchantId = $response->json('merchant.id');

        $this->assertFalse(is_numeric($userId), 'User id should not be a sequential integer.');
        $this->assertFalse(is_numeric($merchantId), 'Merchant id should not be a sequential integer.');
    }
}
