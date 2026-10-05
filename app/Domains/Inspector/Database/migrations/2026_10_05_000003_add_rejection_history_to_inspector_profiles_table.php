<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspector_profiles', function (Blueprint $table) {
            $table->json('rejection_history')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('inspector_profiles', function (Blueprint $table) {
            $table->dropColumn('rejection_history');
        });
    }
};
