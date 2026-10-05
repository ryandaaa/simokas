<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignUuid('account_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('entry_type', 50);
            // Signed on purpose: PRD 18.12 records debits as negative amounts.
            $table->bigInteger('amount');
            $table->string('note')->nullable();
            $table->timestamps();

            // Last line of defense against double posting or double refund.
            $table->unique(['payment_id', 'entry_type']);
            $table->index(['account_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
