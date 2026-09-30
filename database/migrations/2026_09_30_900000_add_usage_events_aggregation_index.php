<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supports AggregateDailyUsage SUM queries:
 * WHERE merchant_id = ? AND customer_id = ? AND occurred_at in [day_start, day_end)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usage_events', function (Blueprint $table): void {
            $table->index(
                ['merchant_id', 'customer_id', 'occurred_at'],
                'usage_events_merchant_customer_occurred_at_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('usage_events', function (Blueprint $table): void {
            $table->dropIndex('usage_events_merchant_customer_occurred_at_index');
        });
    }
};
