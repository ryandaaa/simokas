<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_photos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('result_id')->constrained('inspection_results')->cascadeOnDelete();
            $table->foreignUuid('result_item_id')->constrained('result_items')->cascadeOnDelete();
            $table->string('storage_path');
            $table->timestamp('captured_at');
            $table->timestamp('stamped_at')->nullable();
            $table->boolean('is_camera_capture')->default(false);
            $table->timestamps();

            $table->index('result_id');
            $table->index('result_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_photos');
    }
};
