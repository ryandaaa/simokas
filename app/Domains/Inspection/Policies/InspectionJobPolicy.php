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
}
