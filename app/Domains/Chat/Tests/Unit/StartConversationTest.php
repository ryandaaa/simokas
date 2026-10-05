<?php

declare(strict_types=1);

use App\Domains\Chat\Actions\StartConversation;
use App\Domains\Chat\Enums\ConversationContext;
use App\Domains\Chat\Exceptions\BlockedUserCannotChatException;
use App\Domains\Chat\Exceptions\ChatWithSelfException;
use App\Domains\Chat\Exceptions\ConversationNotFoundException;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('CHT-T01 creates conversation on first attempt and reuses existing on second attempt (CHT-E9)', function () {
    $action = app(StartConversation::class);

    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $listing = Listing::factory()->create([
        'seller_id' => $seller->id,
    ]);

    // First attempt: creates new conversation
    $conversation1 = $action->handle($buyer, ConversationContext::Listing, $listing->id);

    expect($conversation1)->toBeInstanceOf(Conversation::class)
        ->and($conversation1->context_type)->toBe(ConversationContext::Listing)
        ->and($conversation1->context_id)->toBe($listing->id)
        ->and($conversation1->hasMember($buyer->id))->toBeTrue()
        ->and($conversation1->hasMember($seller->id))->toBeTrue()
        ->and($conversation1->members()->count())->toBe(2);

    expect(Conversation::query()->count())->toBe(1);

    // Second attempt: returns existing conversation without creating new record
    $conversation2 = $action->handle($buyer, ConversationContext::Listing, $listing->id);

    expect($conversation2->id)->toBe($conversation1->id)
        ->and(Conversation::query()->count())->toBe(1);
});

it('CHT-T09 rejects chat with own listing (CHT-E8)', function () {
    $action = app(StartConversation::class);

    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $listing = Listing::factory()->create([
        'seller_id' => $seller->id,
    ]);

    expect(fn () => $action->handle($seller, ConversationContext::Listing, $listing->id))
        ->toThrow(ChatWithSelfException::class);
});

it('CHT-T09 rejects chat when initiator is the same as target in job context', function () {
    $action = app(StartConversation::class);

    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $listing = Listing::factory()->create(['seller_id' => $seller->id]);
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $seller->id,
        'inspector_id' => $seller->id,
    ]);

    expect(fn () => $action->handle($seller, ConversationContext::Job, $job->id, $seller->id))
        ->toThrow(ChatWithSelfException::class);
});

it('rejects blocked user from starting conversation', function () {
    $action = app(StartConversation::class);

    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $blockedBuyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Blocked]);

    $listing = Listing::factory()->create(['seller_id' => $seller->id]);

    expect(fn () => $action->handle($blockedBuyer, ConversationContext::Listing, $listing->id))
        ->toThrow(BlockedUserCannotChatException::class);
});

it('throws not found exception when context does not exist', function () {
    $action = app(StartConversation::class);

    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    expect(fn () => $action->handle($buyer, ConversationContext::Listing, '00000000-0000-0000-0000-000000000000'))
        ->toThrow(ConversationNotFoundException::class);
});
