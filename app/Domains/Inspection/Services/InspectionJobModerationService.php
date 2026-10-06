<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\CancelJobByAdmin;
use App\Domains\Inspection\Actions\CompleteJob;
use App\Domains\Inspection\Contracts\InspectionJobModerationContract;
use App\Domains\Inspection\Models\InspectionJob;

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
}
