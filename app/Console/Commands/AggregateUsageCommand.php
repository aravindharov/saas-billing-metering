<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AggregateDailyUsage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild daily usage aggregates for a date range.
 *
 * Dispatches one AggregateDailyUsage job per merchant/customer/date
 * combination found in raw usage_events. Safe to run repeatedly —
 * each job recalculates from source-of-truth data.
 */
final class AggregateUsageCommand extends Command
{
    /** @var string */
    protected $signature = 'usage:aggregate
        {--from= : Start date (YYYY-MM-DD, required)}
        {--to= : End date (YYYY-MM-DD, required)}';

    /** @var string */
    protected $description = 'Rebuild daily usage aggregates from raw usage events';

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');

        if (! $from || ! $to) {
            $this->error('Both --from and --to are required.');

            return self::FAILURE;
        }

        try {
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate = Carbon::parse($to)->endOfDay();
        } catch (\Exception) {
            $this->error('Invalid date format. Use YYYY-MM-DD.');

            return self::FAILURE;
        }

        if ($fromDate->isAfter($toDate)) {
            $this->error('--from must be before or equal to --to.');

            return self::FAILURE;
        }

        $this->info("Aggregating usage from {$fromDate->toDateString()} to {$toDate->toDateString()}...");

        $dispatched = 0;

        // Query distinct merchant/customer/date combinations using
        // database-side grouping — never loads raw events into PHP.
        DB::table('usage_events')
            ->select('merchant_id', 'customer_id', DB::raw('DATE(occurred_at) as usage_date'))
            ->whereBetween('occurred_at', [$fromDate, $toDate])
            ->groupBy('merchant_id', 'customer_id', DB::raw('DATE(occurred_at)'))
            ->orderBy('merchant_id')
            ->chunk(500, function ($rows) use (&$dispatched): void {
                foreach ($rows as $row) {
                    AggregateDailyUsage::dispatch(
                        (int) $row->merchant_id,
                        (int) $row->customer_id,
                        (string) $row->usage_date,
                    );
                    $dispatched++;
                }
            });

        $this->info("Dispatched {$dispatched} aggregation jobs.");

        return self::SUCCESS;
    }
}
