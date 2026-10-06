<?php

declare(strict_types=1);

namespace App\Domains\Notification\Livewire;

use App\Domains\Notification\Models\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Lonceng notifikasi dengan jumlah belum dibaca (NTF-R3).
 *
 * Real-time (Pusher) ditambahkan di NTF-2; saat ini jumlah dimuat ulang
 * setiap render. Untuk tamu, lonceng tidak ditampilkan.
 */
final class NotificationBell extends Component
{
    public function render(): View
    {
        $unreadCount = auth()->check()
            ? Notification::query()->forUser((string) auth()->id())->unread()->count()
            : 0;

        return view('notification::livewire.notification-bell', [
            'unreadCount' => $unreadCount,
        ]);
    }
}
