<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->timestamp('billing_period_start');
            $table->timestamp('billing_period_end');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('total');
            $table->string('status', 32);
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['subscription_id', 'billing_period_start', 'billing_period_end'],
                'invoices_subscription_period_unique',
            );
            $table->index(['merchant_id', 'billing_period_start']);
            $table->index(['merchant_id', 'customer_id']);
            $table->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
