<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InvoiceApiTest extends TestCase
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

    public function test_member_can_list_invoices_for_merchant(): void
    {
        $invoice = $this->invoiceForMerchant($this->merchant);

        $response = $this->actingAs($this->member)->getJson('/api/v1/invoices');

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $invoice->public_id);
        $response->assertJsonPath('data.0.total', $invoice->total);
    }

    public function test_show_invoice_includes_lines(): void
    {
        $invoice = $this->invoiceForMerchant($this->merchant);
        $invoice->lines()->create([
            'type' => 'base',
            'description' => 'Base',
            'quantity' => 1,
            'unit_price' => 50000,
            'amount' => 50000,
        ]);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/invoices/'.$invoice->public_id);

        $response->assertOk();
        $response->assertJsonPath('data.lines.0.type', 'base');
        $response->assertJsonPath('data.lines.0.amount', 50000);
    }

    public function test_merchant_b_cannot_view_merchant_a_invoice(): void
    {
        $invoice = $this->invoiceForMerchant($this->merchant);
        $otherUser = User::factory()->owner()->forMerchant(Merchant::factory()->create())->create();

        $this->actingAs($otherUser)
            ->getJson('/api/v1/invoices/'.$invoice->public_id)
            ->assertNotFound();
    }

    public function test_invoice_list_is_scoped_to_authenticated_merchant(): void
    {
        $this->invoiceForMerchant($this->merchant);
        $otherMerchant = Merchant::factory()->create();
        $this->invoiceForMerchant($otherMerchant);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/invoices');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    private function invoiceForMerchant(Merchant $merchant): Invoice
    {
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $subscription = Subscription::factory()->forMerchant($merchant)->forCustomer($customer)->create();

        return Invoice::factory()->forSubscription($subscription)->create([
            'merchant_id' => $merchant->id,
        ]);
    }
}
