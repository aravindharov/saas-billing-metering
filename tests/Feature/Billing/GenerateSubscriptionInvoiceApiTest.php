<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class GenerateSubscriptionInvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private User $member;

    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $this->member = User::factory()->member()->forMerchant($this->merchant)->create();

        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $this->subscription = Subscription::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($customer)
            ->forPlan($plan)
            ->create([
                'current_period_start' => Carbon::parse('2026-01-01 00:00:00', 'UTC'),
                'current_period_end' => Carbon::parse('2026-02-01 00:00:00', 'UTC'),
            ]);
    }

    public function test_owner_can_generate_invoice_for_ended_period(): void
    {
        $response = $this->actingAs($this->owner)->postJson(
            '/api/v1/subscriptions/'.$this->subscription->public_id.'/generate-invoice',
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'issued');
        $response->assertJsonStructure(['data' => ['id', 'total', 'lines']]);
    }

    public function test_member_cannot_generate_invoice(): void
    {
        $this->actingAs($this->member)->postJson(
            '/api/v1/subscriptions/'.$this->subscription->public_id.'/generate-invoice',
        )->assertForbidden();
    }

    public function test_generate_invoice_rejects_open_billing_period(): void
    {
        $open = Subscription::factory()->forMerchant($this->merchant)->create([
            'current_period_end' => Carbon::now()->addWeek(),
        ]);

        $this->actingAs($this->owner)->postJson(
            '/api/v1/subscriptions/'.$open->public_id.'/generate-invoice',
        )->assertUnprocessable();
    }

    public function test_other_merchant_cannot_generate_invoice(): void
    {
        $other = User::factory()->owner()->forMerchant(Merchant::factory()->create())->create();

        $this->actingAs($other)->postJson(
            '/api/v1/subscriptions/'.$this->subscription->public_id.'/generate-invoice',
        )->assertNotFound();
    }
}
