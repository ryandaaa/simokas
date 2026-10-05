<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Contracts;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;

/**
 * Seam for inspection-job based private access (CAT-R7, CAT-T07).
 *
 * The Inspection domain does not exist yet (JOB-2). Catalog must still be
 * able to grant exact data (address, coordinates, chassis number) to the
 * inspector holding the job, so this Catalog-owned contract defines the
 * check and ships with a default deny-all implementation
 * (DenyInspectionJobAccess). JOB-2 will bind a real implementation that
 * consults inspection jobs. Documented for JOB-2 in the detail controller.
 */
interface InspectionJobAccess
{
    public function canViewPrivate(?User $viewer, Listing $listing): bool;
}
