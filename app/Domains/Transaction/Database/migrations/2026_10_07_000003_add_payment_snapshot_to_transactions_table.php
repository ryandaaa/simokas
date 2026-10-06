<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Payment opened for this transaction (TRX-2). Nullable: set when
            // the buyer opens the payment bill.
            $table->foreignUuid('payment_id')->nullable()->constrained('payments')->nullOnDelete();

            // Commission snapshots taken when the payment succeeds (TRX-2).
            // Absolute rupiah amounts; the seller's share never enters the ledger.
            $table->unsignedBigInteger('inspector_commission')->nullable();
            $table->unsignedBigInteger('platform_commission')->nullable();

            $table->timestamp('completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropColumn(['payment_id', 'inspector_commission', 'platform_commission', 'completed_at']);
        });
    }
};
