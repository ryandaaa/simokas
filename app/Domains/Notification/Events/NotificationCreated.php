<?php

declare(strict_types=1);

namespace App\Domains\Notification\Events;

use App\Domains\Notification\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Disiarkan setiap ada notifikasi baru atau yang diperbarui (NTF-2).
 *
 * Dikirim sinkron (ShouldBroadcastNow) agar tidak menumpuk di antrean saat
 * worker tidak jalan. Di kabel menjadi `private-user.{id}` dengan nama
 * event `notification.created` sesuai docs/flows/notification.md.
 * Tanpa kredensial Pusher, UI memakai polling sebagai cadangan (NTF-E1).
 */
final class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Notification $notification,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->notification->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type->value,
            'title' => $this->notification->title,
        ];
    }
}
