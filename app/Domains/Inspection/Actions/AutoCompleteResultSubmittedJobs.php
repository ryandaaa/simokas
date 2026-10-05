<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Setting\Contracts\SettingsReader;
use App\Support\Clock;
use Illuminate\Database\Eloquent\Collection;

/**
 * Action terjadwal untuk menyelesaikan job result_submitted yang tidak ditanggapi pemohon (D-03, JOB-R9, JOB-T11).
 *
 * Mengambil auto_accept_days (default 3 hari) dari pengaturan, dan menyelesaikan job lewat CompleteJob.
 * Dijalankan harian oleh scheduler.
 * Idempoten: job yang sudah diselesaikan tidak diproses ulang.
 */
final class AutoCompleteResultSubmittedJobs
{
    public function __construct(
        private readonly SettingsReader $settings,
        private readonly CompleteJob $completeJob,
    ) {}

    public function handle(): int
    {
        $autoAcceptDays = $this->settings->getAutoAcceptDays();
        $now = Clock::now();
        $cutoff = $now->copy()->subDays($autoAcceptDays);
        $completedCount = 0;

        InspectionJob::query()
            ->where('status', JobStatus::ResultSubmitted->value)
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '<=', $cutoff)
            ->chunkById(100, function (Collection $jobs) use (&$completedCount): void {
                foreach ($jobs as $job) {
                    $this->completeJob->handle($job);
                    $completedCount++;
                }
            });

        return $completedCount;
    }
}
