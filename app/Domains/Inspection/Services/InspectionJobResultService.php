<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Services;

use App\Domains\Inspection\Contracts\InspectionJobResultContract;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Support\Clock;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class InspectionJobResultService implements InspectionJobResultContract
{
    public function markResultSubmitted(string $jobId): InspectionJob
    {
        return DB::transaction(function () use ($jobId): InspectionJob {
            $now = Clock::now();

            $affected = InspectionJob::query()
                ->whereKey($jobId)
                ->where('status', JobStatus::InProgress->value)
                ->update([
                    'status' => JobStatus::ResultSubmitted->value,
                    'submitted_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($affected === 0) {
                /** @var InspectionJob|null $job */
                $job = InspectionJob::query()->find($jobId);

                if ($job === null) {
                    throw new ModelNotFoundException;
                }

                if ($job->status === JobStatus::ResultSubmitted) {
                    return $job;
                }

                throw InvalidJobStatusTransitionException::alreadyProcessed($job->status);
            }

            /** @var InspectionJob $updatedJob */
            $updatedJob = InspectionJob::query()->findOrFail($jobId);

            return $updatedJob;
        });
    }

    public function reopenJob(string $jobId): InspectionJob
    {
        return DB::transaction(function () use ($jobId): InspectionJob {
            $now = Clock::now();

            $affected = InspectionJob::query()
                ->whereKey($jobId)
                ->where('status', JobStatus::ResultSubmitted->value)
                ->update([
                    'status' => JobStatus::InProgress->value,
                    'updated_at' => $now,
                ]);

            if ($affected === 0) {
                /** @var InspectionJob|null $job */
                $job = InspectionJob::query()->find($jobId);

                if ($job === null) {
                    throw new ModelNotFoundException;
                }

                if ($job->status === JobStatus::InProgress) {
                    return $job;
                }

                throw InvalidJobStatusTransitionException::alreadyProcessed($job->status);
            }

            /** @var InspectionJob $updatedJob */
            $updatedJob = InspectionJob::query()->findOrFail($jobId);

            return $updatedJob;
        });
    }
}
