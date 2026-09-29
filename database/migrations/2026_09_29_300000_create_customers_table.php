<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('external_reference')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
            $table->index(['merchant_id', 'email']);
            $table->unique(['merchant_id', 'external_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
