<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Subscription;
use App\Services\Billing\BillingCalculator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GenerateInvoice
{
    public function __construct(
        private readonly BillingCalculator $calculator,
    ) {}

    public function execute(Subscription $subscription): Invoice
    {
        if ($subscription->current_period_end->isFuture()) {
            throw ValidationException::withMessages([
                'subscription' => ['The billing period has not ended yet.'],
            ]);
        }

        $periodStart = $subscription->current_period_start->copy()->utc();
        $periodEnd = $subscription->current_period_end->copy()->utc();

        $existing = Invoice::where('subscription_id', $subscription->id)
            ->where('billing_period_start', $periodStart)
            ->where('billing_period_end', $periodEnd)
            ->first();

        if ($existing) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($subscription, $periodStart, $periodEnd): Invoice {
                $subscription = Subscription::where('id', $subscription->id)->lockForUpdate()->firstOrFail();

                $existing = Invoice::where('subscription_id', $subscription->id)
                    ->where('billing_period_start', $periodStart)
                    ->where('billing_period_end', $periodEnd)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $subscription->load(['planChanges.fromPlan', 'planChanges.toPlan']);

                $calculation = $this->calculator->calculate($subscription);
                $now = Carbon::now();

                $invoice = new Invoice;
                $invoice->merchant_id = $subscription->merchant_id;
                $invoice->customer_id = $subscription->customer_id;
                $invoice->subscription_id = $subscription->id;
                $invoice->billing_period_start = $periodStart;
                $invoice->billing_period_end = $periodEnd;
                $invoice->subtotal = $calculation->subtotal;
                $invoice->total = $calculation->total;
                $invoice->status = InvoiceStatus::Issued;
                $invoice->issued_at = $now;
                $invoice->save();

                foreach ($calculation->lines as $line) {
                    $row = new InvoiceLine;
                    $row->invoice_id = $invoice->id;
                    $row->type = $line->type;
                    $row->description = $line->description;
                    $row->quantity = $line->quantity;
                    $row->unit_price = $line->unitPrice;
                    $row->amount = $line->amount;
                    $row->metadata = $line->metadata;
                    $row->save();
                }

                return $invoice->refresh()->load(['customer', 'subscription.plan', 'lines']);
            });
        } catch (UniqueConstraintViolationException) {
            return Invoice::where('subscription_id', $subscription->id)
                ->where('billing_period_start', $periodStart)
                ->where('billing_period_end', $periodEnd)
                ->firstOrFail();
        }
    }
}
