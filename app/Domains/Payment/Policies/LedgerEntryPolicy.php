<?php

declare(strict_types=1);

namespace App\Domains\Payment\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Contracts\InspectorEligibility;

final class LedgerEntryPolicy
{
    public function __construct(private readonly InspectorEligibility $eligibility) {}

    public function viewEarnings(User $user): bool
    {
        return $this->eligibility->canViewAndTakeJobs($user);
    }
}
