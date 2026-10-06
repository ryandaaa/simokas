<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->index(['status', 'vehicle_type', 'created_at'], 'listings_status_vtype_created_at_idx');
            $table->index(['status', 'vehicle_type', 'price'], 'listings_status_vtype_price_idx');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->dropIndex('listings_status_vtype_created_at_idx');
            $table->dropIndex('listings_status_vtype_price_idx');
        });
    }
};
