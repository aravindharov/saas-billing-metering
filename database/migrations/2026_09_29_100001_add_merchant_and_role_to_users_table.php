<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->ulid('public_id')->unique()->after('id');
            $table->foreignId('merchant_id')->after('public_id')
                ->constrained('merchants')
                ->cascadeOnDelete();
            $table->string('role', 20)->default('member')->after('password');

            // Email is unique per merchant, not globally. Drop the global unique
            // and replace with a composite unique constraint.
            $table->dropUnique(['email']);
            $table->unique(['merchant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['merchant_id', 'email']);
            $table->dropForeign(['merchant_id']);
            $table->dropColumn(['public_id', 'merchant_id', 'role']);
            $table->unique('email');
        });
    }
};
