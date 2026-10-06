<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('listing_id')->constrained();
            $table->foreignUuid('buyer_id')->constrained('users');
            $table->enum('status', ['offered', 'accepted', 'paid_held', 'completed', 'rejected', 'cancelled', 'expired'])->default('offered');
            $table->unsignedBigInteger('price_amount');
            $table->unsignedSmallInteger('commission_pct')->nullable();
            $table->foreignUuid('inspector_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Unique constraint: one active transaction per listing
            // Uses STORED generated column to work with MySQL 8 and MariaDB
            // NULL in terminal states allows multiple completed/rejected transactions per listing
            $table->string('active_listing_id', 36)->nullable()->storedAs(
                "(case when `status` in ('offered', 'accepted', 'paid_held') then rtrim(`listing_id`) else null end)"
            )->unique();

            // Indexes for common queries
            $table->index('buyer_id');
            $table->index('listing_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
