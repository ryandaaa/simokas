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

    $listing = Listing::factory()->active()->create([
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

    expect(fn () => $action->handle($seller, ConversationContext::Job, $job->id))
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

it('CHT-R2 authorizes only job participants to start job conversation and rejects outsiders', function (string $actorScenario, bool $shouldSucceed) {
    $action = app(StartConversation::class);

    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $requester = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $assignedInspector = User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]);
    $otherInspector = User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]);
    $outsiderBuyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Active]);
    $blockedUser = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Blocked]);

    $listing = Listing::factory()->create(['seller_id' => $seller->id]);
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'inspector_id' => $assignedInspector->id,
    ]);

    $actor = match ($actorScenario) {
        'pemohon' => $requester,
        'penjual' => $seller,
        'inspektor pengambil' => $assignedInspector,
        'inspektor lain' => $otherInspector,
        'pembeli luar' => $outsiderBuyer,
        'admin' => $admin,
        'pengguna terblokir' => $blockedUser,
        default => throw new InvalidArgumentException("Unknown scenario: {$actorScenario}"),
    };

    if ($shouldSucceed) {
        $conversation = $action->handle($actor, ConversationContext::Job, $job->id);
        expect($conversation)->toBeInstanceOf(Conversation::class)
            ->and($conversation->hasMember($actor->id))->toBeTrue();
    } else {
        expect(fn () => $action->handle($actor, ConversationContext::Job, $job->id))
            ->toThrow(\App\Support\Exceptions\DomainException::class);
    }
})->with([
    'pemohon' => ['pemohon', true],
    'penjual' => ['penjual', true],
    'inspektor pengambil' => ['inspektor pengambil', true],
    'inspektor lain' => ['inspektor lain', false],
    'pembeli luar' => ['pembeli luar', false],
    'admin' => ['admin', false],
    'pengguna terblokir' => ['pengguna terblokir', false],
]);

it('CHT-R2 rejects starting job conversation when job has no inspector', function () {
    $action = app(StartConversation::class);

    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $requester = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $listing = Listing::factory()->create(['seller_id' => $seller->id]);
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'inspector_id' => null,
    ]);

    expect(fn () => $action->handle($requester, ConversationContext::Job, $job->id))
        ->toThrow(\App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException::class);

    expect(fn () => $action->handle($seller, ConversationContext::Job, $job->id))
        ->toThrow(\App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException::class);

    expect(Conversation::query()->count())->toBe(0);
});

it('CHT-R2 allows buyer on public listings and rejects outsiders on non-public listings', function (string $status, bool $isPublic) {
    $action = app(StartConversation::class);

    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $listing = Listing::factory()->create([
        'seller_id' => $seller->id,
        'status' => $status,
    ]);

    if ($isPublic) {
        $conversation = $action->handle($buyer, ConversationContext::Listing, $listing->id);
        expect($conversation)->toBeInstanceOf(Conversation::class)
            ->and($conversation->hasMember($buyer->id))->toBeTrue()
            ->and($conversation->hasMember($seller->id))->toBeTrue();
    } else {
        expect(fn () => $action->handle($buyer, ConversationContext::Listing, $listing->id))
            ->toThrow(\App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException::class);
        expect(Conversation::query()->count())->toBe(0);
    }
})->with([
    'active' => ['active', true],
    'in_inspection' => ['in_inspection', true],
    'in_transaction' => ['in_transaction', true],
    'pending_review' => ['pending_review', false],
    'rejected' => ['rejected', false],
    'inactive' => ['inactive', false],
    'sold' => ['sold', false],
]);

it('CHT-R2 rejects outsider roles on non-public listing', function (string $roleScenario) {
    $action = app(StartConversation::class);

    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $listing = Listing::factory()->create([
        'seller_id' => $seller->id,
        'status' => 'pending_review',
    ]);

    $actor = match ($roleScenario) {
        'inspektor lain' => User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]),
        'pembeli luar' => User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]),
        'admin' => User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Active]),
        default => throw new InvalidArgumentException("Unknown scenario: {$roleScenario}"),
    };

    expect(fn () => $action->handle($actor, ConversationContext::Listing, $listing->id))
        ->toThrow(\App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException::class);

    expect(Conversation::query()->count())->toBe(0);
})->with([
    'inspektor lain' => ['inspektor lain'],
    'pembeli luar' => ['pembeli luar'],
    'admin' => ['admin'],
]);

it('CHT-T13 creates job conversation via forJobTaken and rejects unassigned inspector or invalid target', function () {
    $action = app(StartConversation::class);

    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $assignedInspector = User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]);
    $otherInspector = User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]);
    $outsider = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $listing = Listing::factory()->create(['seller_id' => $seller->id]);
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $assignedInspector->id,
    ]);

    // Unassigned inspector cannot call forJobTaken
    expect(fn () => $action->forJobTaken($otherInspector, $job->id))
        ->toThrow(\App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException::class);

    // Invalid target (not seller or requester) is rejected
    expect(fn () => $action->forJobTaken($assignedInspector, $job->id, $outsider->id))
        ->toThrow(\App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException::class);

    // Valid call succeeds
    $conversation = $action->forJobTaken($assignedInspector, $job->id, $seller->id);
    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->and($conversation->hasMember($assignedInspector->id))->toBeTrue()
        ->and($conversation->hasMember($seller->id))->toBeTrue();
});


