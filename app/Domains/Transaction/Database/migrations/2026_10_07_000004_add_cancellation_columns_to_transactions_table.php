<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TRX-3: cancellation bookkeeping columns.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->uuid('cancelled_by')->nullable()->after('completed_at');
            $table->text('cancel_reason')->nullable()->after('cancelled_by');
            $table->timestamp('cancel_requested_at')->nullable()->after('cancel_reason');
            $table->timestamp('needs_attention_at')->nullable()->after('cancel_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn(['cancelled_by', 'cancel_reason', 'cancel_requested_at', 'needs_attention_at']);
        });
    }
};
