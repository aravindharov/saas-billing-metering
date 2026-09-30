<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\GenerateSubscriptionInvoice;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class GenerateInvoicesCommand extends Command
{
    /** @var string */
    protected $signature = 'billing:generate-invoices
        {--before= : Generate for periods ending on or before this UTC datetime (default: now)}';

    /** @var string */
    protected $description = 'Queue invoice generation for subscriptions whose billing period has ended';

    public function handle(): int
    {
        $before = $this->option('before')
            ? Carbon::parse((string) $this->option('before'))->utc()
            : Carbon::now()->utc();

        $this->info('Queuing invoice generation for periods ending on or before '.$before->toIso8601String());

        $dispatched = 0;

        Subscription::query()
            ->where('current_period_end', '<=', $before)
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$dispatched): void {
                foreach ($subscriptions as $subscription) {
                    $exists = Invoice::where('subscription_id', $subscription->id)
                        ->where('billing_period_start', $subscription->current_period_start)
                        ->where('billing_period_end', $subscription->current_period_end)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    GenerateSubscriptionInvoice::dispatch($subscription->id);
                    $dispatched++;
                }
            });

        $this->info("Dispatched {$dispatched} invoice job(s).");

        return self::SUCCESS;
    }
}
