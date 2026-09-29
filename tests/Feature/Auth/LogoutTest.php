<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout(): void
    {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->owner()->forMerchant($merchant)->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertOk();
        $response->assertJsonPath('message', 'Logged out.');
    }

    public function test_logged_out_token_cannot_access_protected_endpoints(): void
    {
        $merchant = Merchant::factory()->create(['slug' => 'test-logout']);
        $user = User::factory()->owner()->forMerchant($merchant)->create([
            'email' => 'logout@test.co',
        ]);

        // Login to get a real token.
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'test-logout',
            'email' => 'logout@test.co',
            'password' => 'password',
        ]);

        $token = $loginResponse->json('token');

        // Verify the token works.
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();

        // Reset the auth guard so the next request resolves the token fresh.
        Auth::forgetGuards();

        // Logout.
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        Auth::forgetGuards();

        // Token should no longer work.
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_unauthenticated_request_to_logout_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $merchant = Merchant::factory()->create(['slug' => 'multi-token']);
        $user = User::factory()->owner()->forMerchant($merchant)->create([
            'email' => 'multi@test.co',
        ]);

        // Create two tokens via login.
        $login1 = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'multi-token',
            'email' => 'multi@test.co',
            'password' => 'password',
        ]);
        $token1 = $login1->json('token');

        $login2 = $this->postJson('/api/v1/auth/login', [
            'merchant' => 'multi-token',
            'email' => 'multi@test.co',
            'password' => 'password',
        ]);
        $token2 = $login2->json('token');

        Auth::forgetGuards();

        // Logout token 1.
        $this->withToken($token1)->postJson('/api/v1/auth/logout')->assertOk();

        Auth::forgetGuards();

        // Token 1 is revoked.
        $this->withToken($token1)->getJson('/api/v1/auth/me')->assertUnauthorized();

        Auth::forgetGuards();

        // Token 2 still works.
        $this->withToken($token2)->getJson('/api/v1/auth/me')->assertOk();
    }
}
