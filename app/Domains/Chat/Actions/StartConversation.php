<?php

declare(strict_types=1);

namespace App\Domains\Chat\Actions;

use App\Domains\Chat\Enums\ConversationContext;
use App\Domains\Chat\Exceptions\BlockedUserCannotChatException;
use App\Domains\Chat\Exceptions\ChatWithSelfException;
use App\Domains\Chat\Exceptions\ConversationNotFoundException;
use App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Database\Eloquent\Model;
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
    ): Conversation {
        if ($initiator->isBlocked()) {
            throw BlockedUserCannotChatException::create();
        }

        $type = is_string($contextType) ? ConversationContext::from($contextType) : $contextType;

        [$otherUserId, $contextRecord] = match ($type) {
            ConversationContext::Listing => $this->resolveListingContext($initiator, $contextId),
            ConversationContext::Job => $this->resolveJobContext($initiator, $contextId),
        };

        if ($initiator->id === $otherUserId) {
            throw ChatWithSelfException::create($type === ConversationContext::Listing);
        }

        return $this->findOrCreateConversation($initiator, $type, $contextId, $otherUserId, $contextRecord);
    }

    /**
     * Dedicated method for the JobTaken event listener to create a conversation between inspector and seller/requester.
     * CHT-R2, CHT-T13
     */
    public function forJobTaken(
        User $inspector,
        string $jobId,
        ?string $targetUserId = null,
    ): Conversation {
        if ($inspector->isBlocked()) {
            throw BlockedUserCannotChatException::create();
        }

        $job = InspectionJob::query()->find($jobId);

        if (! $job) {
            throw ConversationNotFoundException::contextNotFound();
        }

        if ($job->inspector_id !== $inspector->id) {
            throw UnauthorizedChatParticipantException::create();
        }

        $listing = Listing::query()->find($job->listing_id);

        if (! $listing) {
            throw ConversationNotFoundException::contextNotFound();
        }

        $target = $targetUserId ?? $listing->seller_id;

        // Verify target is legitimate job participant (seller or requester)
        if ($target !== $listing->seller_id && $target !== $job->requester_id) {
            throw UnauthorizedChatParticipantException::create();
        }

        if ($inspector->id === $target) {
            throw ChatWithSelfException::create(false);
        }

        return $this->findOrCreateConversation($inspector, ConversationContext::Job, $job->id, $target, $job);
    }

    /**
     * @return array{0: string, 1: Listing}
     */
    private function resolveListingContext(User $initiator, string $listingId): array
    {
        $listing = Listing::query()->find($listingId);

        if (! $listing) {
            throw ConversationNotFoundException::contextNotFound();
        }

        if ($initiator->id === $listing->seller_id) {
            throw ChatWithSelfException::create(isOwnListing: true);
        }

        // Non-public listings (draft, pending_review, rejected, inactive, sold) cannot be used as context by outsiders
        if (! $listing->status->isPubliclyVisible()) {
            throw UnauthorizedChatParticipantException::create();
        }

        return [$listing->seller_id, $listing];
    }

    /**
     * @return array{0: string, 1: InspectionJob}
     */
    private function resolveJobContext(User $initiator, string $jobId): array
    {
        $job = InspectionJob::query()->find($jobId);

        if (! $job) {
            throw ConversationNotFoundException::contextNotFound();
        }

        $listing = Listing::query()->find($job->listing_id);

        if (! $listing) {
            throw ConversationNotFoundException::contextNotFound();
        }

        // Job without inspector cannot start conversation via user path
        if ($job->inspector_id === null) {
            throw UnauthorizedChatParticipantException::create();
        }

        // On job context, initiator ONLY allowed to be requester, seller, or assigned inspector
        $allowedParticipants = array_filter([
            $job->inspector_id,
            $listing->seller_id,
            $job->requester_id,
        ]);

        if (! in_array($initiator->id, $allowedParticipants, true)) {
            throw UnauthorizedChatParticipantException::create();
        }

        // Interlocutor resolution:
        // If initiator is the inspector, interlocutor is the seller.
        // If initiator is seller or requester, interlocutor is the inspector.
        if ($initiator->id === $job->inspector_id) {
            $otherUserId = $listing->seller_id;
        } else {
            $otherUserId = $job->inspector_id;
        }

        return [$otherUserId, $job];
    }

    private function findOrCreateConversation(
        User $initiator,
        ConversationContext $type,
        string $contextId,
        string $otherUserId,
        Model $contextRecord,
    ): Conversation {
        $userIds = [$initiator->id, $otherUserId];
        sort($userIds);
        $lockKey = sprintf('start_conversation:%s:%s:%s:%s', $type->value, $contextId, $userIds[0], $userIds[1]);

        return Cache::lock($lockKey, 10)->block(5, function () use ($type, $contextId, $initiator, $otherUserId) {
            return DB::transaction(function () use ($type, $contextId, $initiator, $otherUserId) {
                // Database-level row lock on context record to serialize creation independently of cache driver
                match ($type) {
                    ConversationContext::Listing => Listing::query()->whereKey($contextId)->lockForUpdate()->first(),
                    ConversationContext::Job => InspectionJob::query()->whereKey($contextId)->lockForUpdate()->first(),
                };

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
}

