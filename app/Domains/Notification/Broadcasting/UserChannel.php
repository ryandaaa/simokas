<?php

declare(strict_types=1);

namespace App\Domains\Notification\Broadcasting;

use App\Domains\Identity\Models\User;

/**
 * Otorisasi kanal broadcast privat `user.{id}` (NTF-2).
 *
 * Di kabel menjadi `private-user.{id}` sesuai docs/flows/notification.md.
 * Hanya pemilik notifikasi yang boleh berlangganan (NTF-R1, NTF-E4).
 */
final class UserChannel
{
    public function join(mixed $user, string $userId): bool
    {
        return $user instanceof User
            && (string) $user->getAuthIdentifier() === $userId
            && ! $user->isBlocked();
    }
}
