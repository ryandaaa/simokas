<?php

declare(strict_types=1);

use App\Domains\Chat\Broadcasting\ConversationChannel;
use App\Domains\Notification\Broadcasting\UserChannel;
use Illuminate\Support\Facades\Broadcast;

/*
 * Kanal broadcast privat (CHT-2, NTF-2).
 *
 * Di kabel, PrivateChannel('conversation.{id}') menjadi
 * `private-conversation.{id}` dan PrivateChannel('user.{id}') menjadi
 * `private-user.{id}` sesuai docs/flows/chat.md dan notification.md.
 * Tanpa kredensial Pusher, UI memakai polling sebagai cadangan (NTF-E1).
 * Driver `pusher` diimplementasikan di App\Support\Broadcasting
 * (tanpa SDK tambahan) dan didaftarkan di AppServiceProvider.
 */

Broadcast::channel('conversation.{conversationId}', ConversationChannel::class);
Broadcast::channel('user.{userId}', UserChannel::class);
