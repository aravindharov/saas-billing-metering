<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('base_price');
            $table->string('billing_cycle', 20);
            $table->unsignedBigInteger('included_usage_units');
            $table->unsignedBigInteger('overage_rate');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['merchant_id', 'name']);
            $table->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
