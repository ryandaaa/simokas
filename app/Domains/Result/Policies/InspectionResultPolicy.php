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

    public function uploadPhoto(User $user, InspectionResult $result): bool
    {
        return $this->updateDraft($user, $result);
    }

    public function deletePhoto(User $user, InspectionResult $result, \App\Domains\Result\Models\ResultPhoto $photo): bool
    {
        if (! $this->updateDraft($user, $result)) {
            return false;
        }

        return $photo->result_id === $result->id;
    }

    public function viewPhoto(User $user, InspectionResult $result, \App\Domains\Result\Models\ResultPhoto $photo): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        if ($photo->result_id !== $result->id) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInspector() && (string) $user->getKey() === (string) $result->inspector_id) {
            return true;
        }

        // Draft results cannot be viewed by other users (RES-R2, RES-E11)
        if ($result->status === ResultStatus::Draft) {
            return false;
        }

        // For submitted results: listing owner and inspection requester can view
        if ($result->status === ResultStatus::Submitted) {
            if ((string) $user->getKey() === (string) $result->listing->seller_id) {
                return true;
            }

            if ($result->job->requester_id !== null && (string) $user->getKey() === (string) $result->job->requester_id) {
                return true;
            }
        }

        return false;
    }
}

