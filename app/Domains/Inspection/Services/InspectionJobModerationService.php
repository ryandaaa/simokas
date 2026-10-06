<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\CancelJobByAdmin;
use App\Domains\Inspection\Actions\CompleteJob;
use App\Domains\Inspection\Contracts\InspectionJobModerationContract;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use Illuminate\Database\Eloquent\Collection;

final class InspectionJobModerationService implements InspectionJobModerationContract
{
    public function __construct(
        private readonly CancelJobByAdmin $cancelJobByAdmin,
        private readonly CompleteJob $completeJob,
    ) {}

    public function cancelJob(User $admin, InspectionJob $job, ?string $reason = null, bool $refund = false): InspectionJob
    {
        return $this->cancelJobByAdmin->handle($admin, $job, $reason, $refund);
    }

    public function completeJob(InspectionJob $job): InspectionJob
    {
        return $this->completeJob->handle($job);
    }

    public function activeJobsForUsers(array $userIds): Collection
    {
        if ($userIds === []) {
            /** @var Collection<int, InspectionJob> $empty */
            $empty = new Collection;

            return $empty;
        }

        /** @var Collection<int, InspectionJob> $jobs */
        $jobs = InspectionJob::query()
            ->whereNotIn('status', [JobStatus::Completed->value, JobStatus::Cancelled->value, JobStatus::Expired->value])
            ->where(function ($query) use ($userIds): void {
                $query->whereIn('requester_id', $userIds)
                    ->orWhereIn('inspector_id', $userIds);
            })
            ->get();

        return $jobs;
    }
}
