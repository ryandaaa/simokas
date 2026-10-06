<?php

declare(strict_types=1);

namespace App\Domains\Notification\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Notification\Models\Notification;

/**
 * Notifikasi hanya dapat dibaca pemiliknya (NTF-R1).
 */
final class NotificationPolicy
{
    public function view(User $user, Notification $notification): bool
    {
        return $this->owns($user, $notification);
    }

    public function update(User $user, Notification $notification): bool
    {
        return $this->owns($user, $notification);
    }

    private function owns(User $user, Notification $notification): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return $notification->user_id === $user->getKey();
    }
}
