<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Billing\GenerateInvoice;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GenerateSubscriptionInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly int $subscriptionId,
    ) {}

    public function handle(GenerateInvoice $action): void
    {
        $subscription = Subscription::find($this->subscriptionId);

        if ($subscription === null) {
            return;
        }

        $action->execute($subscription);
    }
}
