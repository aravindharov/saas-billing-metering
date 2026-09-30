<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Actions\Billing\GenerateInvoice;
use App\Enums\InvoiceStatus;
use App\Models\DailyUsage;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class GenerateInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private GenerateInvoice $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $this->action = app(GenerateInvoice::class);
    }

    public function test_generates_issued_invoice_with_lines_for_ended_period(): void
    {
        $subscription = $this->endedSubscription(basePrice: 50000, included: 1000, overageRate: 200);

        DailyUsage::factory()->create([
            'merchant_id' => $subscription->merchant_id,
            'customer_id' => $subscription->customer_id,
            'usage_date' => '2026-01-15',
            'total_quantity' => 1500,
        ]);

        $invoice = $this->action->execute($subscription->fresh());

        $this->assertSame(InvoiceStatus::Issued, $invoice->status);
        $this->assertSame(150000, $invoice->total);
        $this->assertCount(2, $invoice->lines);
        $this->assertNotNull($invoice->issued_at);
    }

    public function test_historical_pricing_uses_subscription_snapshot_not_current_plan_price(): void
    {
        $plan = Plan::factory()->forMerchant($this->merchant)->create(['base_price' => 10000]);
        $subscription = $this->endedSubscription(
            basePrice: 10000,
            included: 1000,
            overageRate: 5,
            plan: $plan,
        );

        $plan->update(['base_price' => 20000]);

        $invoice = $this->action->execute($subscription->fresh());

        $this->assertSame(10000, $invoice->total);
    }

    public function test_generating_same_invoice_twice_is_idempotent(): void
    {
        $subscription = $this->endedSubscription(basePrice: 9900, included: 100, overageRate: 5);

        $first = $this->action->execute($subscription->fresh());
        $second = $this->action->execute($subscription->fresh());

        $this->assertTrue($first->is($second));
        $this->assertSame(1, Invoice::where('subscription_id', $subscription->id)->count());
    }

    public function test_rejects_invoice_before_period_end(): void
    {
        $subscription = Subscription::factory()->forMerchant($this->merchant)->create([
            'current_period_end' => Carbon::now()->addDay(),
        ]);

        $this->expectException(ValidationException::class);

        $this->action->execute($subscription);
    }

    private function endedSubscription(
        int $basePrice,
        int $included,
        int $overageRate,
        ?Plan $plan = null,
    ): Subscription {
        $plan ??= Plan::factory()->forMerchant($this->merchant)->create([
            'base_price' => $basePrice,
            'included_usage_units' => $included,
            'overage_rate' => $overageRate,
        ]);

        return Subscription::factory()
            ->forMerchant($this->merchant)
            ->forPlan($plan)
            ->create([
                'base_price' => $basePrice,
                'included_usage_units' => $included,
                'overage_rate' => $overageRate,
                'current_period_start' => Carbon::parse('2026-01-01 00:00:00', 'UTC'),
                'current_period_end' => Carbon::parse('2026-02-01 00:00:00', 'UTC'),
                'started_at' => Carbon::parse('2026-01-01 00:00:00', 'UTC'),
            ]);
    }
}
