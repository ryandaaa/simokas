<?php

declare(strict_types=1);

namespace App\Domains\Chat\Actions;

use App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class GetMissedMessages
{
    /**
     * Retrieve missed messages in conversation after a specific message id.
     * CHT-E1, CHT-T07
     *
     * @return Collection<int, Message>
     */
    public function handle(User $user, Conversation $conversation, ?string $afterMessageId = null): Collection
    {
        if (! $conversation->hasMember($user->id)) {
            throw UnauthorizedChatParticipantException::create();
        }

        $query = $conversation->messages()->orderBy('created_at', 'asc');

        if ($afterMessageId !== null) {
            $lastMessage = Message::query()->find($afterMessageId);
            if ($lastMessage && $lastMessage->conversation_id === $conversation->id && $lastMessage->created_at !== null) {
                $query->where('created_at', '>', $lastMessage->created_at);
            }
        }

        return $query->get();
    }
}
