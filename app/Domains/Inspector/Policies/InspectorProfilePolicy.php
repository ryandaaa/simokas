<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Models\InspectorProfile;

final class InspectorProfilePolicy
{
    public function view(User $user, InspectorProfile $profile): bool
    {
        return $user->isAdmin() || $user->id === $profile->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isInspector();
    }

    public function update(User $user, InspectorProfile $profile): bool
    {
        return $user->isAdmin() || ($user->isInspector() && $user->id === $profile->user_id);
    }

    public function verify(User $user, ?InspectorProfile $profile = null): bool
    {
        return $user->isAdmin();
    }

    public function resubmit(User $user, InspectorProfile $profile): bool
    {
        return $user->isInspector() && $user->id === $profile->user_id;
    }
}
