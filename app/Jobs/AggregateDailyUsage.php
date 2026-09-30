<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DailyUsage;
use App\Models\UsageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Aggregate raw usage events into a daily usage read model.
 *
 * IDEMPOTENT: always recalculates from source-of-truth (usage_events)
 * using SUM(quantity), then upserts the total. Retries and duplicate
 * dispatches produce identical results — never double-counts.
 */
final class AggregateDailyUsage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        public readonly int $merchantId,
        public readonly int $customerId,
        public readonly string $usageDate,
    ) {}

    public function handle(): void
    {
        $dayStart = Carbon::parse($this->usageDate, 'UTC')->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();

        $total = UsageEvent::where('merchant_id', $this->merchantId)
            ->where('customer_id', $this->customerId)
            ->where('occurred_at', '>=', $dayStart)
            ->where('occurred_at', '<', $dayEnd)
            ->sum('quantity');

        if ($total > 0) {
            DB::table('daily_usage')->upsert(
                [
                    'merchant_id' => $this->merchantId,
                    'customer_id' => $this->customerId,
                    'usage_date' => $this->usageDate,
                    'total_quantity' => $total,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                ['merchant_id', 'customer_id', 'usage_date'],
                ['total_quantity', 'updated_at'],
            );
        } else {
            DailyUsage::where('merchant_id', $this->merchantId)
                ->where('customer_id', $this->customerId)
                ->where('usage_date', $this->usageDate)
                ->delete();
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('aggregation.daily_usage_failed', [
            'merchant_id' => $this->merchantId,
            'customer_id' => $this->customerId,
            'usage_date' => $this->usageDate,
            'message' => $exception->getMessage(),
        ]);
    }
}
