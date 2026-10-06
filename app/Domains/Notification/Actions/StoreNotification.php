<?php

declare(strict_types=1);

namespace App\Domains\Notification\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Notification\Enums\NotificationType;
use App\Domains\Notification\Models\Notification;

/**
 * Menyimpan notifikasi untuk satu penerima.
 *
 * Idempoten (NTF-E2): pasangan kejadian dan penerima diwakili dedupe_key
 * yang unik, sehingga event yang dipancarkan dua kali tidak menghasilkan
 * notifikasi ganda. Penerima yang diblokir atau tidak ditemukan dilewati
 * tanpa error (NTF-R6, NTF-E3).
 */
final class StoreNotification
{
    public function handle(
        string $userId,
        NotificationType $type,
        string $title,
        string $body,
        ?string $link,
        string $dedupeKey,
        ?array $data = null,
    ): ?Notification {
        $user = User::query()->find($userId);

        if (! $user instanceof User || $user->isBlocked()) {
            return null;
        }

        return Notification::query()->firstOrCreate(
            ['dedupe_key' => $dedupeKey],
            [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'link' => $link,
                'data' => $data,
            ],
        );
    }

    /**
     * Simpan atau perbarui notifikasi pesan chat (NTF-R4).
     *
     * Pesan beruntun dari percakapan yang sama dikelompokkan menjadi satu
     * baris per penerima: bila sudah ada notifikasi belum dibaca untuk
     * percakapan ini, isinya diperbarui (jumlah pesan bertambah). Bila yang
     * ada sudah dibaca, baris yang sama dipakai ulang dan ditandai belum
     * dibaca lagi.
     */
    public function handleChatMessage(
        string $userId,
        string $conversationId,
        string $senderName,
        string $link,
    ): ?Notification {
        $user = User::query()->find($userId);

        if (! $user instanceof User || $user->isBlocked()) {
            return null;
        }

        $dedupeKey = "chat:{$conversationId}:{$userId}";

        /** @var Notification|null $existing */
        $existing = Notification::query()
            ->where('dedupe_key', $dedupeKey)
            ->first();

        if ($existing instanceof Notification && ! $existing->isRead()) {
            $count = (int) ($existing->data['count'] ?? 1) + 1;

            $existing->update([
                'body' => __('notification.chat.body_grouped', ['count' => $count, 'nama' => $senderName]),
                'data' => ['count' => $count],
                'link' => $link,
            ]);

            return $existing->refresh();
        }

        if ($existing instanceof Notification) {
            $existing->update([
                'title' => __('notification.chat.title'),
                'body' => __('notification.chat.body_single', ['nama' => $senderName]),
                'data' => ['count' => 1],
                'link' => $link,
                'read_at' => null,
            ]);

            return $existing->refresh();
        }

        return Notification::query()->create([
            'user_id' => $userId,
            'type' => NotificationType::MessageReceived,
            'title' => __('notification.chat.title'),
            'body' => __('notification.chat.body_single', ['nama' => $senderName]),
            'link' => $link,
            'dedupe_key' => $dedupeKey,
            'data' => ['count' => 1],
        ]);
    }
}
