<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notifikasi dalam web (NTF-1).
     *
     * dedupe_key menjamin NTF-E2: event yang dipancarkan dua kali tidak
     * menghasilkan notifikasi ganda untuk penerima yang sama. Untuk pesan
     * chat, dedupe_key juga menjadi kunci pengelompokan (NTF-R4): satu baris
     * per percakapan per penerima, diperbarui saat ada pesan beruntun.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 32)->index();
            $table->string('title');
            $table->text('body');
            $table->string('link')->nullable();
            $table->string('dedupe_key')->unique();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
