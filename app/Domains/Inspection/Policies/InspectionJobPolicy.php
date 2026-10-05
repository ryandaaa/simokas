<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;

final class InspectionJobPolicy
{
    public function request(User $user, Listing $listing): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        if ($user->isInspector() || $user->isAdmin()) {
            return false;
        }

        return true;
    }

    public function view(User $user, InspectionJob $job): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return $user->isUser() && $user->getKey() === $job->requester_id;
    }

    public function viewDetail(User $user, InspectionJob $job): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isInspector()
            && $job->inspector_id !== null
            && (string) $user->getKey() === (string) $job->inspector_id;
    }

    public function viewPrivate(User $user, InspectionJob $job): bool
    {
        return $this->viewDetail($user, $job);
    }

    public function take(User $user, InspectionJob $job): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return $user->isInspector();
    }

    public function schedule(User $user, InspectionJob $job): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return $user->isInspector()
            && $job->inspector_id !== null
            && (string) $user->getKey() === (string) $job->inspector_id
            && $job->status->value === 'taken';
    }

    public function start(User $user, InspectionJob $job): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return $user->isInspector()
            && $job->inspector_id !== null
            && (string) $user->getKey() === (string) $job->inspector_id
            && $job->status->value === 'scheduled';
    }

    public function cancelAsInspector(User $user, InspectionJob $job): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return $user->isInspector()
            && $job->inspector_id !== null
            && (string) $user->getKey() === (string) $job->inspector_id
            && in_array($job->status->value, ['taken', 'scheduled'], true);
    }

    public function cancelAsRequester(User $user, InspectionJob $job): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return (string) $user->getKey() === (string) $job->requester_id
            && in_array($job->status->value, ['requested', 'paid', 'taken', 'scheduled'], true);
    }

    public function cancelAsAdmin(User $user, InspectionJob $job): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return $user->isAdmin() && $job->isActive();
    }
}
