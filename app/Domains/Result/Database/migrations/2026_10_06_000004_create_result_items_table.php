<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('result_id')->constrained('inspection_results')->cascadeOnDelete();
            $table->foreignUuid('template_item_id')->constrained('template_items')->cascadeOnDelete();
            $table->string('rating', 32)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['result_id', 'template_item_id']);
            $table->index('result_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_items');
    }
};
