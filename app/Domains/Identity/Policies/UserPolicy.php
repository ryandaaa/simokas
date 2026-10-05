<?php

declare(strict_types=1);

namespace App\Domains\Identity\Policies;

use App\Domains\Identity\Models\User;

final class UserPolicy
{
    public function viewAny(User $currentUser): bool
    {
        return $currentUser->isAdmin();
    }

    public function manage(User $currentUser): bool
    {
        return $currentUser->isAdmin();
    }

    public function view(User $currentUser, User $targetUser): bool
    {
        return $currentUser->isAdmin() || $currentUser->id === $targetUser->id;
    }

    public function update(User $currentUser, User $targetUser): bool
    {
        return $currentUser->isAdmin() || $currentUser->id === $targetUser->id;
    }

    public function delete(User $currentUser, User $targetUser): bool
    {
        return $currentUser->isAdmin();
    }

    /**
     * D-06: Inspektor dilarang membeli kendaraan di platform.
     */
    public function buy(User $user): bool
    {
        return $user->canBuy();
    }

    /**
     * D-06: Inspektor dilarang menjual kendaraan di platform.
     */
    public function sell(User $user): bool
    {
        return $user->canSell();
    }

    /**
     * D-06: Inspektor dilarang mengajukan inspeksi.
     */
    public function requestInspection(User $user): bool
    {
        return $user->canRequestInspection();
    }
}
