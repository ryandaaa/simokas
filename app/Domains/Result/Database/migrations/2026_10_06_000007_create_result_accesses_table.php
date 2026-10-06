<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_accesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('result_id')->index();
            $table->uuid('user_id')->index();
            $table->uuid('payment_id')->nullable()->unique();
            $table->timestamps();

            $table->foreign('result_id')
                ->references('id')
                ->on('inspection_results')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->cascadeOnDelete();

            // Unique constraint: one access per user per result
            $table->unique(['result_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_accesses');
    }
};
