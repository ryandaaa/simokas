<?php

declare(strict_types=1);

namespace App\Domains\Chat\Policies;

use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Models\User;

final class ConversationPolicy
{
    /**
     * Determine whether the user can view the conversation.
     * CHT-R3, CHT-E3: Only participants can read the conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        if ($user->isBlocked()) {
            return false;
        }

        return $conversation->hasMember($user->id);
    }

    /**
     * Determine whether the user can send messages in the conversation.
     * CHT-R3, CHT-R6: Only participants who are not blocked can send messages.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if ($user->isBlocked()) {
            return false;
        }

        return $conversation->hasMember($user->id);
    }

    /**
     * Authorize private broadcast channel: conversation.{id}
     * CHT-T03: Only active participant can join the private channel.
     */
    public function viewChannel(User $user, string $conversationId): bool
    {
        if ($user->isBlocked()) {
            return false;
        }

        $conversation = Conversation::query()->find($conversationId);

        if (! $conversation) {
            return false;
        }

        return $conversation->hasMember($user->id);
    }
}
