<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Contracts;

use App\Domains\Inspection\Models\InspectionJob;

interface InspectionJobResultContract
{
    /**
     * Atomically transitions the job to ResultSubmitted.
     * Uses conditional update (WHERE status = in_progress).
     * Idempotent: returns the job if already ResultSubmitted.
     */
    public function markResultSubmitted(string $jobId): InspectionJob;

    /**
     * Atomically transitions the job back to InProgress upon admin reopening.
     * Uses conditional update (WHERE status = result_submitted).
     * Idempotent: returns the job if already InProgress.
     */
    public function reopenJob(string $jobId): InspectionJob;
}
