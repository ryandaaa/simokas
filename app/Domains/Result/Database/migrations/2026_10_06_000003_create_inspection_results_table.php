<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_results', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('job_id')->unique()->constrained('inspection_jobs')->cascadeOnDelete();
            $table->foreignUuid('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignUuid('inspector_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('template_id')->constrained('inspection_templates')->restrictOnDelete();
            $table->decimal('total_score', 5, 2)->nullable();
            $table->string('recommendation', 32)->nullable();
            $table->text('summary_note')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['inspector_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_results');
    }
};
