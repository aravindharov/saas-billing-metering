<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_usage', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('usage_date');
            $table->unsignedBigInteger('total_quantity');
            $table->timestamps();

            // One aggregate row per merchant/customer/day. This constraint
            // also serves as the primary lookup index for the read API.
            $table->unique(['merchant_id', 'customer_id', 'usage_date']);

            // Merchant-wide date-range queries (e.g. all customers for Sep 2026).
            $table->index(['merchant_id', 'usage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_usage');
    }
};
