<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Support\Clock;
use Illuminate\Support\Facades\DB;

/**
 * Action terjadwal untuk menandai job yang memerlukan perhatian admin (JOB-R10, JOB-E4, JOB-T12).
 *
 * 1. Job taken yang belum dijadwalkan setelah 48 jam (JOB-R10).
 * 2. Job scheduled yang melewati waktu jadwal tanpa dimulai inspeksinya.
 *
 * Mengisi kolom needs_attention_at.
 * Dijalankan tiap jam oleh scheduler.
 * Idempoten: job yang sudah ditandai tidak diproses ulang.
 */
final class MarkJobsNeedingAttention
{
    public function handle(): int
    {
        return DB::transaction(function (): int {
            $now = Clock::now();
            $takenCutoff = $now->copy()->subHours(48);

            // 1. Taken tanpa jadwal setelah 48 jam (JOB-R10)
            $affectedTaken = InspectionJob::query()
                ->where('status', JobStatus::Taken->value)
                ->whereNull('scheduled_at')
                ->where('taken_at', '<=', $takenCutoff)
                ->whereNull('needs_attention_at')
                ->update([
                    'needs_attention_at' => $now,
                    'updated_at' => $now,
                ]);

            // 2. Scheduled melewati waktu jadwal tanpa dimulai
            $affectedScheduled = InspectionJob::query()
                ->where('status', JobStatus::Scheduled->value)
                ->where('scheduled_at', '<=', $now)
                ->whereNull('needs_attention_at')
                ->update([
                    'needs_attention_at' => $now,
                    'updated_at' => $now,
                ]);

            return $affectedTaken + $affectedScheduled;
        });
    }
}
