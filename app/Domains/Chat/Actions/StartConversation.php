<?php

declare(strict_types=1);

namespace App\Domains\Chat\Actions;

use App\Domains\Chat\Enums\ConversationContext;
use App\Domains\Chat\Exceptions\BlockedUserCannotChatException;
use App\Domains\Chat\Exceptions\ChatWithSelfException;
use App\Domains\Chat\Exceptions\ConversationNotFoundException;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class StartConversation
{
    /**
     * Start or retrieve an existing conversation for the participant pair and context.
     * CHT-R1, CHT-R2, CHT-R8, CHT-E8, CHT-E9
     */
    public function handle(
        User $initiator,
        ConversationContext|string $contextType,
        string $contextId,
        ?string $targetUserId = null,
    ): Conversation {
        if ($initiator->isBlocked()) {
            throw BlockedUserCannotChatException::create();
        }

        $type = is_string($contextType) ? ConversationContext::from($contextType) : $contextType;

        $otherUserId = match ($type) {
            ConversationContext::Listing => $this->resolveListingOtherUserId($initiator, $contextId),
            ConversationContext::Job => $this->resolveJobOtherUserId($initiator, $contextId, $targetUserId),
        };

        if ($initiator->id === $otherUserId) {
            throw ChatWithSelfException::create($type === ConversationContext::Listing);
        }

        $userIds = [$initiator->id, $otherUserId];
        sort($userIds);
        $lockKey = sprintf('start_conversation:%s:%s:%s:%s', $type->value, $contextId, $userIds[0], $userIds[1]);

        return Cache::lock($lockKey, 10)->block(5, function () use ($type, $contextId, $initiator, $otherUserId) {
            return DB::transaction(function () use ($type, $contextId, $initiator, $otherUserId) {
                /** @var Conversation|null $existing */
                $existing = Conversation::query()
                    ->where('context_type', $type)
                    ->where('context_id', $contextId)
                    ->whereHas('members', fn ($q) => $q->where('user_id', $initiator->id))
                    ->whereHas('members', fn ($q) => $q->where('user_id', $otherUserId))
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $conversation = Conversation::query()->create([
                    'context_type' => $type,
                    'context_id' => $contextId,
                ]);

                $conversation->members()->createMany([
                    ['user_id' => $initiator->id],
                    ['user_id' => $otherUserId],
                ]);

                return $conversation;
            });
        });
    }

    private function resolveListingOtherUserId(User $initiator, string $listingId): string
    {
        $listing = Listing::query()->find($listingId);

        if (! $listing) {
            throw ConversationNotFoundException::contextNotFound();
        }

        if ($initiator->id === $listing->seller_id) {
            throw ChatWithSelfException::create(isOwnListing: true);
        }

        return $listing->seller_id;
    }

    private function resolveJobOtherUserId(User $initiator, string $jobId, ?string $explicitTargetUserId): string
    {
        if ($explicitTargetUserId !== null) {
            return $explicitTargetUserId;
        }

        $job = InspectionJob::query()->find($jobId);

        if (! $job) {
            throw ConversationNotFoundException::contextNotFound();
        }

        $listing = Listing::query()->find($job->listing_id);

        if (! $listing) {
            throw ConversationNotFoundException::contextNotFound();
        }

        $sellerId = $listing->seller_id;
        $inspectorId = $job->inspector_id;

        if ($initiator->id === $inspectorId) {
            return $sellerId;
        }

        if ($initiator->id === $sellerId && $inspectorId !== null) {
            return $inspectorId;
        }

        if ($inspectorId !== null) {
            return $inspectorId;
        }

        return $sellerId;
    }
}
