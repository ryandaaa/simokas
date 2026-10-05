<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobScheduled;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Exceptions\InvalidScheduleTimeException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Support\Clock;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Action untuk inspektor menetapkan jadwal inspeksi (JOB-3).
 *
 * Transisi dari taken menjadi scheduled.
 * Hanya dapat dilakukan oleh inspektor pengambil job.
 * Jadwal harus di masa depan dan disimpan dalam UTC.
 */
final class ScheduleJob
{
    public function __construct(
        private readonly Dispatcher $events,
    ) {}

    public function handle(User $inspector, InspectionJob $job, DateTimeInterface|string $scheduledAt): InspectionJob
    {
        // Pastikan aktor adalah inspektor yang mengambil job
        if ($job->inspector_id === null || (string) $job->inspector_id !== (string) $inspector->getKey()) {
            throw InvalidJobStatusTransitionException::alreadyProcessed($job->status);
        }

        // Konversi dan validasi waktu jadwal
        $scheduledCarbon = is_string($scheduledAt)
            ? Carbon::parse($scheduledAt, Clock::TIMEZONE)
            : Carbon::instance($scheduledAt);

        $scheduledAtUtc = $scheduledCarbon->copy()->setTimezone(Clock::TIMEZONE);

        if ($scheduledAtUtc->isPast() || $scheduledAtUtc->equalTo(now())) {
            throw InvalidScheduleTimeException::mustBeInFuture();
        }

        return DB::transaction(function () use ($inspector, $job, $scheduledAtUtc): InspectionJob {
            $affected = InspectionJob::query()
                ->whereKey($job->getKey())
                ->where('status', JobStatus::Taken->value)
                ->where('inspector_id', $inspector->getKey())
                ->update([
                    'status' => JobStatus::Scheduled->value,
                    'scheduled_at' => $scheduledAtUtc,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var InspectionJob $fresh */
                $fresh = InspectionJob::query()->findOrFail($job->getKey());

                if ($fresh->status === JobStatus::Scheduled
                    && (string) $fresh->inspector_id === (string) $inspector->getKey()
                ) {
                    return $fresh;
                }

                throw InvalidJobStatusTransitionException::alreadyProcessed($fresh->status);
            }

            $freshJob = $job->refresh();

            $this->events->dispatch(new JobScheduled(
                jobId: (string) $freshJob->getKey(),
                listingId: (string) $freshJob->listing_id,
                inspectorId: (string) $inspector->getKey(),
                scheduledAt: $scheduledAtUtc->toIso8601String(),
            ));

            return $freshJob;
        });
    }
}
