<?php

declare(strict_types=1);

namespace App\Domains\Chat\Actions;

use App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Models\User;

final class MarkConversationAsRead
{
    /**
     * Mark a conversation as read by updating last_read_at for the user.
     * CHT-R3, CHT-T06
     */
    public function handle(User $user, Conversation $conversation): void
    {
        if (! $conversation->hasMember($user->id)) {
            throw UnauthorizedChatParticipantException::create();
        }

        $conversation->members()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }
}
