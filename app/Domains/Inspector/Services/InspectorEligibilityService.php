<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Contracts\InspectorEligibility;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;

final class InspectorEligibilityService implements InspectorEligibility
{
    public function canViewAndTakeJobs(User $user): bool
    {
        if (! $user->isInspector() || $user->isBlocked()) {
            return false;
        }

        /** @var InspectorProfile|null $profile */
        $profile = InspectorProfile::query()->where('user_id', $user->id)->first();

        return $profile !== null && $profile->verification_status === VerificationStatus::Approved;
    }

    public function getAllowedSpecialties(User $user): array
    {
        if (! $this->canViewAndTakeJobs($user)) {
            return [];
        }

        /** @var InspectorProfile|null $profile */
        $profile = InspectorProfile::query()->where('user_id', $user->id)->first();

        if ($profile === null) {
            return [];
        }

        return $profile->vehicle_types;
    }
}
