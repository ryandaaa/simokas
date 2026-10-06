<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Contracts;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;

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
}
