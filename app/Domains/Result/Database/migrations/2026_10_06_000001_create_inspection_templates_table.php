<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('vehicle_type', 32);
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['vehicle_type', 'version']);
            $table->index(['vehicle_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_templates');
    }
};
