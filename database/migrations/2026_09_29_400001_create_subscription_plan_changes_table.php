<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plan_changes', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('from_plan_id')->constrained('plans');
            $table->foreignId('to_plan_id')->constrained('plans');
            $table->timestamp('effective_at');
            $table->unsignedBigInteger('from_base_price');
            $table->unsignedBigInteger('from_included_usage_units');
            $table->unsignedBigInteger('from_overage_rate');
            $table->string('from_billing_cycle', 20);
            $table->unsignedBigInteger('to_base_price');
            $table->unsignedBigInteger('to_included_usage_units');
            $table->unsignedBigInteger('to_overage_rate');
            $table->string('to_billing_cycle', 20);
            $table->timestamps();

            $table->index(['subscription_id', 'effective_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plan_changes');
    }
};
