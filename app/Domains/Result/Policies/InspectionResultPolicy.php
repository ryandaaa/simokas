<?php

declare(strict_types=1);

namespace App\Domains\Result\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;

final class InspectionResultPolicy
{
    public function fill(User $user, InspectionJob $job): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        if (! $user->isInspector()) {
            return false;
        }

        if ($job->inspector_id === null || (string) $user->getKey() !== (string) $job->inspector_id) {
            return false;
        }

        return $job->status === JobStatus::InProgress;
    }

    public function viewDraft(User $user, InspectionResult $result): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        if (! $user->isInspector()) {
            return false;
        }

        if ((string) $user->getKey() !== (string) $result->inspector_id) {
            return false;
        }

        return $result->status === ResultStatus::Draft;
    }

    public function updateDraft(User $user, InspectionResult $result): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        if (! $user->isInspector()) {
            return false;
        }

        if ((string) $user->getKey() !== (string) $result->inspector_id) {
            return false;
        }

        if ($result->status !== ResultStatus::Draft) {
            return false;
        }

        return $result->job->status === JobStatus::InProgress;
    }
}
