<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_events', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('event_id', 255);
            $table->unsignedInteger('quantity');
            $table->timestamp('occurred_at');
            $table->timestamps();

            // Idempotency: the database-level uniqueness constraint is the final
            // authority that prevents duplicate events, even under concurrent requests.
            $table->unique(['merchant_id', 'event_id']);

            // Query patterns for future aggregation and billing lookups.
            // Each index is append-oriented (new events go to the end of the
            // B-tree leaf chain), keeping write amplification low.
            $table->index(['customer_id', 'occurred_at']);
            $table->index(['subscription_id', 'occurred_at']);
            $table->index(['merchant_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
