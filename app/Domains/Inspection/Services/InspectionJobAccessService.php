<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Services;

use App\Domains\Catalog\Contracts\InspectionJobAccess;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;

final class InspectionJobAccessService implements InspectionJobAccess
{
    public function canViewPrivate(?User $viewer, Listing $listing): bool
    {
        if ($viewer === null || ! $viewer->isInspector() || $viewer->isBlocked() || ! $viewer->isActive()) {
            return false;
        }

        return InspectionJob::query()
            ->where('listing_id', $listing->getKey())
            ->where('inspector_id', $viewer->getKey())
            ->active()
            ->exists();
    }
}
