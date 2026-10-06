<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Contracts;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for admin moderation of inspection jobs.
 */
interface InspectionJobModerationContract
{
    /**
     * Cancel job by admin with optional refund.
     */
    public function cancelJob(User $admin, InspectionJob $job, ?string $reason = null, bool $refund = false): InspectionJob;

    /**
     * Manually complete job by admin (result_submitted -> completed).
     */
    public function completeJob(InspectionJob $job): InspectionJob;

    /**
     * Active (non-final) jobs where any of the given users is the
     * requester or the inspector. Used by user moderation (ADM-4).
     *
     * @param  list<string>  $userIds
     * @return Collection<int, InspectionJob>
     */
    public function activeJobsForUsers(array $userIds): Collection;
}
