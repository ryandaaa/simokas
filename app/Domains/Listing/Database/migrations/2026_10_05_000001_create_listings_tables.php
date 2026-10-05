<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('vehicle_type', 20);
            $table->string('title', 100);
            $table->string('brand', 100);
            $table->string('model', 60);
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('price');
            $table->boolean('negotiable')->default(true);
            $table->unsignedInteger('odometer_km');
            $table->string('transmission', 20);
            $table->string('fuel', 20);
            $table->string('color', 30);
            $table->text('chassis_number')->nullable();
            $table->text('description')->nullable();
            $table->string('area_name', 100)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->text('address_full')->nullable();
            $table->text('inspection_place');
            $table->string('status', 30)->default('pending_review');
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_inspected')->default(false);
            $table->timestamp('last_inspected_at')->nullable();
            $table->foreignUuid('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['vehicle_type', 'status']);
            $table->index(['price', 'year', 'odometer_km']);
        });

        Schema::create('listing_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->text('storage_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();

            $table->index(['listing_id', 'sort_order']);
            $table->index(['listing_id', 'is_cover']);
        });

        Schema::create('listing_inspection_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->timestamp('slot_start');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'slot_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_inspection_slots');
        Schema::dropIfExists('listing_photos');
        Schema::dropIfExists('listings');
    }
};
