<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('template_id')->constrained('inspection_templates')->cascadeOnDelete();
            $table->string('category', 64);
            $table->string('label');
            $table->unsignedInteger('weight')->default(1);
            $table->boolean('is_required')->default(true);
            $table->boolean('requires_photo')->default(false);
            $table->boolean('is_critical')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['template_id', 'category', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_items');
    }
};
