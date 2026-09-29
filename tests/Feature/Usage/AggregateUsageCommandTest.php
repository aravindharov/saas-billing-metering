<?php

declare(strict_types=1);

namespace Tests\Feature\Usage;

use App\Jobs\AggregateDailyUsage;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class AggregateUsageCommandTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private Customer $customer;

    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->customer = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $this->subscription = Subscription::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forPlan($plan)
            ->create();
    }

    public function test_dispatches_jobs_for_date_range(): void
    {
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['occurred_at' => '2026-09-28 10:00:00']);
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['occurred_at' => '2026-09-29 10:00:00']);

        Queue::fake();

        $this->artisan('usage:aggregate', ['--from' => '2026-09-28', '--to' => '2026-09-29'])
            ->assertExitCode(0);

        Queue::assertPushed(AggregateDailyUsage::class, 2);
    }

    public function test_requires_from_and_to_options(): void
    {
        $this->artisan('usage:aggregate')
            ->assertExitCode(1);
    }

    public function test_rejects_from_after_to(): void
    {
        $this->artisan('usage:aggregate', ['--from' => '2026-09-30', '--to' => '2026-09-28'])
            ->assertExitCode(1);
    }

    public function test_rebuild_produces_deterministic_results(): void
    {
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 100, 'occurred_at' => '2026-09-29 10:00:00']);
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 200, 'occurred_at' => '2026-09-29 14:00:00']);

        // Run rebuild synchronously (sync queue).
        $this->artisan('usage:aggregate', ['--from' => '2026-09-29', '--to' => '2026-09-29'])
            ->assertExitCode(0);

        // Process the dispatched jobs.
        $this->processQueuedJobs();

        $first = DailyUsage::where('usage_date', '2026-09-29')->first();
        $this->assertSame(300, $first->total_quantity);

        // Run rebuild again.
        $this->artisan('usage:aggregate', ['--from' => '2026-09-29', '--to' => '2026-09-29'])
            ->assertExitCode(0);

        $this->processQueuedJobs();

        $second = DailyUsage::where('usage_date', '2026-09-29')->first();
        $this->assertSame(300, $second->total_quantity);
        $this->assertDatabaseCount('daily_usage', 1);
    }

    private function processQueuedJobs(): void
    {
        $jobs = DB::table('usage_events')
            ->select('merchant_id', 'customer_id', DB::raw('DATE(occurred_at) as usage_date'))
            ->groupBy('merchant_id', 'customer_id', DB::raw('DATE(occurred_at)'))
            ->get();

        foreach ($jobs as $row) {
            AggregateDailyUsage::dispatchSync((int) $row->merchant_id, (int) $row->customer_id, (string) $row->usage_date);
        }
    }
}
