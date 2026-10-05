<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu listing hanya boleh punya satu job aktif (JOB-R1, PRD 18.7).
     *
     * Pilihan STORED (bukan VIRTUAL): kolom turunan berindeks unik harus
     * berjalan identik di MySQL 8 (dipakai CI) dan MariaDB (lokal 11.8,
     * versi hosting belum pasti). Kolom STORED berindeks didukung penuh di
     * kedua mesin sejak MySQL 5.7 / MariaDB 10.2, sementara kolom VIRTUAL
     * berindeks unik memiliki batasan yang berbeda-beda antar versi.
     *
     * rtrim(`listing_id`) digunakan karena listing_id bertipe CHAR(36)
     * (dari foreignUuid). Di MariaDB, kolom CHAR memerlukan rtrim() di dalam
     * ekspresi kolom turunan agar deterministik dan tidak ditolak oleh
     * error 1901 (aturan trailing space pada collation PAD SPACE).
     * MySQL 8 dan MariaDB 11.8 keduanya mendukung rtrim() pada generated column.
     *
     * Nilai NULL pada status akhir (completed, cancelled, expired) tidak bentrok
     * karena indeks unik mengizinkan banyak NULL di kedua mesin.
     */
    public function up(): void
    {
        Schema::create('inspection_jobs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('listing_id')->constrained('listings')->restrictOnDelete();
            $table->foreignUuid('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('requested')->index();
            $table->unsignedBigInteger('fee_amount');
            $table->unsignedTinyInteger('platform_fee_pct');
            $table->timestamp('expires_at')->index();
            $table->timestamp('available_since')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('needs_attention_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->foreignUuid('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('active_listing_id', 36)->nullable()->storedAs(
                "(case when `status` in ('completed', 'cancelled', 'expired') then null else rtrim(`listing_id`) end)"
            )->unique();
            $table->timestamps();

            $table->index(['listing_id', 'status']);
            $table->index(['requester_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_jobs');
    }
};
