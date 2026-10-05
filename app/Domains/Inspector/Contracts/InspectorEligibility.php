<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Contracts;

use App\Domains\Identity\Models\User;

interface InspectorEligibility
{
    /**
     * Determines whether an inspector is approved and eligible to view and take inspection jobs.
     */
    public function canViewAndTakeJobs(User $user): bool;

    /**
     * Returns the array of vehicle types (e.g. ['car', 'motorcycle']) that this inspector is allowed to inspect.
     *
     * @return array<int, string>
     */
    public function getAllowedSpecialties(User $user): array;
}
