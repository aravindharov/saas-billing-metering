<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class InputHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_create_ignores_untrusted_merchant_id_in_payload(): void
    {
        $merchant = Merchant::factory()->create();
        $other = Merchant::factory()->create();
        $owner = User::factory()->owner()->forMerchant($merchant)->create();

        $response = $this->actingAs($owner)->postJson('/api/v1/customers', [
            'name' => 'Test Co',
            'email' => 'test@example.com',
            'merchant_id' => $other->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('customers', [
            'email' => 'test@example.com',
            'merchant_id' => $merchant->id,
        ]);
    }

    public function test_subscription_create_uses_plan_snapshot_not_client_pricing(): void
    {
        $merchant = Merchant::factory()->create();
        $owner = User::factory()->owner()->forMerchant($merchant)->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create([
            'base_price' => 9900,
            'included_usage_units' => 1000,
            'overage_rate' => 5,
        ]);

        $response = $this->actingAs($owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $customer->public_id,
            'plan_id' => $plan->public_id,
            'base_price' => 1,
            'included_usage_units' => 1,
            'overage_rate' => 1,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.base_price', 9900);
        $response->assertJsonPath('data.included_usage_units', 1000);
        $response->assertJsonPath('data.overage_rate', 5);
    }

    public function test_invoice_routes_are_read_only(): void
    {
        $invoiceRoutes = collect(Route::getRoutes())
            ->filter(fn ($route) => str_contains($route->uri(), 'api/v1/invoices'));

        foreach (['PUT', 'PATCH', 'DELETE'] as $method) {
            $this->assertFalse(
                $invoiceRoutes->contains(fn ($route) => in_array($method, $route->methods(), true)),
                "Unexpected {$method} on invoice routes",
            );
        }
    }
}
