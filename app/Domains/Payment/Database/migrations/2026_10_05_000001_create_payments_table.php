<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payer_id')->constrained('users')->cascadeOnDelete();
            $table->string('purpose', 50)->index();
            $table->uuid('ref_id')->index();
            $table->unsignedBigInteger('amount');
            $table->string('status', 50)->default('pending')->index();
            $table->string('method', 50)->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['purpose', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
