<?php

declare(strict_types=1);

namespace App\Domains\Notification\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Notification\Models\Notification;

/**
 * Tandai semua notifikasi milik pengguna sebagai dibaca (NTF-R7).
 *
 * @return int jumlah notifikasi yang ditandai
 */
final class MarkAllNotificationsAsRead
{
    public function handle(User $user): int
    {
        return Notification::query()
            ->forUser($user->getKey())
            ->unread()
            ->update(['read_at' => now()]);
    }
}
