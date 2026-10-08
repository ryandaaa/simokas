<?php

declare(strict_types=1);

namespace App\Domains\Chat\Broadcasting;

use App\Domains\Chat\Policies\ConversationPolicy;
use App\Domains\Identity\Models\User;

/**
 * Otorisasi kanal broadcast privat `conversation.{id}` (CHT-2).
 *
 * Di kabel menjadi `private-conversation.{id}` sesuai docs/flows/chat.md.
 * Hanya anggota percakapan yang boleh berlangganan (CHT-R3, CHT-E3).
 */
final class ConversationChannel
{
    public function join(mixed $user, string $conversationId): bool
    {
        return $user instanceof User
            && app(ConversationPolicy::class)->viewChannel($user, $conversationId);
    }
}
