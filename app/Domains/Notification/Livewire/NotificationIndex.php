<?php

declare(strict_types=1);

namespace App\Domains\Notification\Livewire;

use App\Domains\Notification\Actions\MarkAllNotificationsAsRead;
use App\Domains\Notification\Actions\MarkNotificationAsRead;
use App\Domains\Notification\Models\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman daftar notifikasi: lonceng ringkas, daftar, tandai dibaca (NTF-R7).
 */
final class NotificationIndex extends Component
{
    use WithPagination;

    /**
     * Buka notifikasi: tandai dibaca lalu arahkan ke tautan tujuan.
     */
    public function openNotification(string $id): mixed
    {
        /** @var Notification $notification */
        $notification = Notification::query()->findOrFail($id);

        $this->authorize('update', $notification);

        app(MarkNotificationAsRead::class)->handle($notification);

        return $this->redirect($notification->link ?? route('notifications.index'));
    }

    public function markAllAsRead(): void
    {
        app(MarkAllNotificationsAsRead::class)->handle(auth()->user());

        $this->resetPage();
    }

    public function render(): View
    {
        $userId = (string) auth()->id();

        $notifications = Notification::query()
            ->forUser($userId)
            ->latest()
            ->paginate(15);

        $unreadCount = Notification::query()
            ->forUser($userId)
            ->unread()
            ->count();

        return view('notification::livewire.notification-index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ])->layout('components.layouts.app', ['title' => __('notification.index.title')]);
    }
}
