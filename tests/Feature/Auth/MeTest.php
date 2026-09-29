<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_retrieve_me(): void
    {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->owner()->forMerchant($merchant)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'role'],
            'merchant' => ['id', 'name', 'slug'],
        ]);
    }

    public function test_me_returns_correct_merchant(): void
    {
        $merchant = Merchant::factory()->create(['name' => 'Test Corp', 'slug' => 'test-corp']);
        $user = User::factory()->member()->forMerchant($merchant)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonPath('merchant.slug', 'test-corp');
        $response->assertJsonPath('merchant.name', 'Test Corp');
    }

    public function test_me_does_not_expose_another_merchants_data(): void
    {
        $merchantA = Merchant::factory()->create(['slug' => 'merchant-a']);
        $merchantB = Merchant::factory()->create(['slug' => 'merchant-b']);

        $userA = User::factory()->owner()->forMerchant($merchantA)->create();

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonPath('merchant.slug', 'merchant-a');
        $this->assertNotEquals('merchant-b', $response->json('merchant.slug'));
    }

    public function test_me_does_not_return_password(): void
    {
        $merchant = Merchant::factory()->create();
        $user = User::factory()->owner()->forMerchant($merchant)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonMissingPath('user.password');
    }

    public function test_unauthenticated_request_to_me_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }
}
