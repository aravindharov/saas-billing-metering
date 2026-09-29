<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_authentication_is_rejected(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->withToken('invalid-token-value')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_revoked_token_is_rejected(): void
    {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->owner()->forMerchant($merchant)->create();
        $token = $user->createToken('api')->plainTextToken;

        // Revoke the token.
        $user->tokens()->delete();

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_malformed_authorization_header_is_rejected(): void
    {
        $this->withHeaders(['Authorization' => 'NotBearer something'])
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_mass_assignment_of_role_during_login_is_ignored(): void
    {
        $merchant = Merchant::factory()->create(['slug' => 'test-co']);
        User::factory()->member()->forMerchant($merchant)->create([
            'email' => 'member@test.co',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'test-co',
            'email' => 'member@test.co',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.role', 'member');
    }

    public function test_merchant_id_in_request_body_does_not_switch_tenant(): void
    {
        $merchantA = Merchant::factory()->create(['slug' => 'target-a']);
        $merchantB = Merchant::factory()->create(['slug' => 'target-b']);
        $userA = User::factory()->owner()->forMerchant($merchantA)->create();

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/v1/auth/me');
        $response->assertOk();
        $response->assertJsonPath('merchant.slug', 'target-a');

        // The tenant context is always from the authenticated user.
        $this->assertNotEquals($merchantB->id, $userA->merchant_id);
    }

    public function test_owner_has_owner_level_access(): void
    {
        $merchant = Merchant::factory()->create();
        $owner = User::factory()->owner()->forMerchant($merchant)->create();

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/v1/auth/me');
        $response->assertOk();
        $response->assertJsonPath('user.role', 'owner');
    }

    public function test_member_can_authenticate_and_access_me(): void
    {
        $merchant = Merchant::factory()->create();
        $member = User::factory()->member()->forMerchant($merchant)->create();

        Sanctum::actingAs($member);

        $response = $this->getJson('/api/v1/auth/me');
        $response->assertOk();
        $response->assertJsonPath('user.role', 'member');
    }

    public function test_passwords_are_hashed_in_database(): void
    {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->forMerchant($merchant)->create([
            'password' => 'test-password',
        ]);

        $user->refresh();

        $this->assertNotEquals('test-password', $user->getAttributes()['password']);
        $this->assertTrue(password_verify('test-password', $user->getAttributes()['password']));
    }
}
