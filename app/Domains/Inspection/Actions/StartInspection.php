<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobStarted;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Models\InspectionJob;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Action untuk inspektor memulai pelaksanaan inspeksi (JOB-3).
 *
 * Transisi dari scheduled menjadi in_progress.
 * Hanya dapat dilakukan oleh inspektor pengambil job.
 * Mulai inspeksi sebelum status scheduled ditolak (JOB-E10).
 * Mencatat started_at (JOB-R11).
 */
final class StartInspection
{
    public function __construct(
        private readonly Dispatcher $events,
    ) {}

    public function handle(User $inspector, InspectionJob $job): InspectionJob
    {
        // Pastikan aktor adalah inspektor yang mengambil job
        if ($job->inspector_id === null || (string) $job->inspector_id !== (string) $inspector->getKey()) {
            throw InvalidJobStatusTransitionException::alreadyProcessed($job->status);
        }

        // JOB-E10: Mulai inspeksi sebelum dijadwalkan ditolak
        if ($job->status !== JobStatus::Scheduled) {
            throw InvalidJobStatusTransitionException::fromStatus($job->status, JobStatus::InProgress);
        }

        return DB::transaction(function () use ($inspector, $job): InspectionJob {
            $now = now();

            $affected = InspectionJob::query()
                ->whereKey($job->getKey())
                ->where('status', JobStatus::Scheduled->value)
                ->where('inspector_id', $inspector->getKey())
                ->update([
                    'status' => JobStatus::InProgress->value,
                    'started_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($affected === 0) {
                /** @var InspectionJob $fresh */
                $fresh = InspectionJob::query()->findOrFail($job->getKey());

                if ($fresh->status === JobStatus::InProgress
                    && (string) $fresh->inspector_id === (string) $inspector->getKey()
                ) {
                    return $fresh;
                }

                throw InvalidJobStatusTransitionException::alreadyProcessed($fresh->status);
            }

            $freshJob = $job->refresh();

            $this->events->dispatch(new JobStarted(
                jobId: (string) $freshJob->getKey(),
                listingId: (string) $freshJob->listing_id,
                inspectorId: (string) $inspector->getKey(),
            ));

            return $freshJob;
        });
    }
}
