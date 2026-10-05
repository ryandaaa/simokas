<?php

declare(strict_types=1);

namespace App\Domains\Setting\Policies;

use App\Domains\Identity\Models\User;

final class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }
}
