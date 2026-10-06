<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->unique()->constrained('transactions')->cascadeOnDelete();
            $table->foreignUuid('inspector_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->text('comment')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->index('inspector_id');
            $table->index('buyer_id');
            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
