<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Contracts\InspectionJobAccess;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;

/**
 * Default InspectionJobAccess: deny everyone.
 *
 * The Inspection domain (JOB-2) does not exist yet, so no job inspector can
 * be resolved. JOB-2 must bind its own implementation of
 * InspectionJobAccess in a shared PR. Until then only owners and admins
 * (via ListingPolicy::viewPrivate) see exact data.
 */
final class DenyInspectionJobAccess implements InspectionJobAccess
{
    public function canViewPrivate(?User $viewer, Listing $listing): bool
    {
        return false;
    }
}
