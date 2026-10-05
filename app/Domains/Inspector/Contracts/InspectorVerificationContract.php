<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Contracts;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Models\InspectorProfile;

interface InspectorVerificationContract
{
    public function approve(User $admin, InspectorProfile $profile): InspectorProfile;

    public function reject(User $admin, InspectorProfile $profile, string $reason): InspectorProfile;
}
