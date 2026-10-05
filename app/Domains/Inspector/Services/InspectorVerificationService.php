<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Actions\ApproveInspector;
use App\Domains\Inspector\Actions\RejectInspector;
use App\Domains\Inspector\Contracts\InspectorVerificationContract;
use App\Domains\Inspector\Models\InspectorProfile;

final class InspectorVerificationService implements InspectorVerificationContract
{
    public function __construct(
        private readonly ApproveInspector $approveInspector,
        private readonly RejectInspector $rejectInspector,
    ) {}

    public function approve(User $admin, InspectorProfile $profile): InspectorProfile
    {
        return $this->approveInspector->handle($admin, $profile);
    }

    public function reject(User $admin, InspectorProfile $profile, string $reason): InspectorProfile
    {
        return $this->rejectInspector->handle($admin, $profile, $reason);
    }
}
