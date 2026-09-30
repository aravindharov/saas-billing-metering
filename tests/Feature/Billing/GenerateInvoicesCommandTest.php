<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Jobs\GenerateSubscriptionInvoice;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class GenerateInvoicesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_jobs_only_for_missing_invoices(): void
    {
        Bus::fake();

        $merchant = Merchant::factory()->create();

        $needsInvoice = Subscription::factory()->forMerchant($merchant)->create([
            'current_period_start' => Carbon::parse('2026-01-01 00:00:00', 'UTC'),
            'current_period_end' => Carbon::parse('2026-02-01 00:00:00', 'UTC'),
        ]);

        $alreadyInvoiced = Subscription::factory()->forMerchant($merchant)->create([
            'current_period_start' => Carbon::parse('2026-01-01 00:00:00', 'UTC'),
            'current_period_end' => Carbon::parse('2026-02-01 00:00:00', 'UTC'),
        ]);
        Invoice::factory()->forSubscription($alreadyInvoiced)->create();

        $stillActivePeriod = Subscription::factory()->forMerchant($merchant)->create([
            'current_period_end' => Carbon::parse('2027-01-01 00:00:00', 'UTC'),
        ]);

        $this->artisan('billing:generate-invoices', ['--before' => '2026-06-01 00:00:00'])
            ->assertSuccessful();

        Bus::assertDispatched(GenerateSubscriptionInvoice::class, 1);
        Bus::assertDispatched(GenerateSubscriptionInvoice::class, function ($job) use ($needsInvoice): bool {
            return $job->subscriptionId === $needsInvoice->id;
        });
        Bus::assertNotDispatched(GenerateSubscriptionInvoice::class, function ($job) use ($alreadyInvoiced, $stillActivePeriod): bool {
            return in_array($job->subscriptionId, [$alreadyInvoiced->id, $stillActivePeriod->id], true);
        });
    }
}
