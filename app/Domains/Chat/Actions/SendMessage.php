<?php

declare(strict_types=1);

namespace App\Domains\Chat\Actions;

use App\Domains\Chat\Events\MessageSent;
use App\Domains\Chat\Exceptions\BlockedUserCannotChatException;
use App\Domains\Chat\Exceptions\InvalidMessageException;
use App\Domains\Chat\Exceptions\MessageRateLimitExceededException;
use App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class SendMessage
{
    /**
     * Send a text message to a conversation.
     * CHT-R3, CHT-R4, CHT-R6, CHT-R9, CHT-E4, CHT-E6
     */
    public function handle(
        User $sender,
        Conversation $conversation,
        string $body,
        ?string $clientKey = null,
    ): Message {
        // CHT-R6: Blocked user cannot send message
        if ($sender->isBlocked()) {
            throw BlockedUserCannotChatException::create();
        }

        // CHT-R3: Only participants can send message
        if (! $conversation->hasMember($sender->id)) {
            throw UnauthorizedChatParticipantException::create();
        }

        // CHT-R4, CHT-E4: Validate text length and empty
        $trimmedBody = trim($body);
        if ($trimmedBody === '') {
            throw InvalidMessageException::empty();
        }

        if (mb_strlen($trimmedBody) > 1000) {
            throw InvalidMessageException::tooLong();
        }

        // CHT-R9: Rate limit 30 messages per minute per user
        $rateKey = 'chat_send_rate:'.$sender->id;
        if (RateLimiter::tooManyAttempts($rateKey, 30)) {
            throw MessageRateLimitExceededException::create();
        }

        // CHT-E6, CHT-T08: Idempotency by client key
        if ($clientKey !== null) {
            $cacheKey = sprintf('chat_client_key:%s:%s:%s', $conversation->id, $sender->id, $clientKey);
            $cachedId = Cache::get($cacheKey);

            if ($cachedId !== null) {
                $cached = Message::query()->find($cachedId);
                if ($cached) {
                    return $cached;
                }
            }

            if (Str::isUuid($clientKey)) {
                $existing = Message::query()->find($clientKey);
                if ($existing) {
                    return $existing;
                }
            }
        }

        RateLimiter::hit($rateKey, 60);

        /** @var Message $message */
        $message = DB::transaction(function () use ($sender, $conversation, $trimmedBody, $clientKey) {
            $attributes = [
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'body' => $trimmedBody,
            ];

            if ($clientKey !== null && Str::isUuid($clientKey)) {
                $attributes['id'] = $clientKey;
            }

            $msg = Message::query()->create($attributes);

            // Update sender's last_read_at
            $conversation->members()
                ->where('user_id', $sender->id)
                ->update(['last_read_at' => $msg->created_at ?? now()]);

            return $msg;
        });

        if ($clientKey !== null) {
            $cacheKey = sprintf('chat_client_key:%s:%s:%s', $conversation->id, $sender->id, $clientKey);
            Cache::put($cacheKey, $message->id, now()->addMinutes(10));
        }

        MessageSent::dispatch($message);

        return $message;
    }
}
