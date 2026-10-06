<?php

declare(strict_types=1);

namespace App\Domains\Notification\Actions;

use App\Domains\Notification\Models\Notification;

/**
 * Tandai satu notifikasi sebagai dibaca (NTF-R7).
 *
 * Idempoten: memanggil dua kali tidak mengubah apa pun pada panggilan kedua.
 * Otorisasi (hanya pemilik) dilakukan pemanggil lewat NotificationPolicy.
 */
final class MarkNotificationAsRead
{
    public function handle(Notification $notification): Notification
    {
        if (! $notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        return $notification->refresh();
    }
}
