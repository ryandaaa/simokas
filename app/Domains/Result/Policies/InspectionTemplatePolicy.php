<?php

declare(strict_types=1);

namespace App\Domains\Result\Policies;

use App\Domains\Identity\Models\User;

final class InspectionTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }
}
