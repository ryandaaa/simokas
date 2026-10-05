<?php

declare(strict_types=1);

use App\Domains\Chat\Actions\GetMissedMessages;
use App\Domains\Chat\Actions\SendMessage;
use App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('CHT-T07 retrieves missed messages created after last known message (CHT-E1)', function () {
    $sendMessage = app(SendMessage::class);
    $getMissed = app(GetMissedMessages::class);

    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    $msg1 = $sendMessage->handle($buyer, $conversation, 'Pesan 1');

    $this->travel(1)->seconds();
    $msg2 = $sendMessage->handle($seller, $conversation, 'Pesan 2');

    $this->travel(1)->seconds();
    $msg3 = $sendMessage->handle($buyer, $conversation, 'Pesan 3');

    // Missed after msg1 should be msg2 and msg3
    $missedAfter1 = $getMissed->handle($seller, $conversation, $msg1->id);
    expect($missedAfter1)->toHaveCount(2)
        ->and($missedAfter1->pluck('id')->all())->toBe([$msg2->id, $msg3->id]);

    // Missed after msg2 should only be msg3
    $missedAfter2 = $getMissed->handle($seller, $conversation, $msg2->id);
    expect($missedAfter2)->toHaveCount(1)
        ->and($missedAfter2->first()->id)->toBe($msg3->id);

    // Missed after msg3 should be empty
    $missedAfter3 = $getMissed->handle($seller, $conversation, $msg3->id);
    expect($missedAfter3)->toBeEmpty();

    // Without afterMessageId: returns all messages
    $all = $getMissed->handle($buyer, $conversation, null);
    expect($all)->toHaveCount(3)
        ->and($all->pluck('id')->all())->toBe([$msg1->id, $msg2->id, $msg3->id]);
});

it('rejects outsider from retrieving missed messages', function () {
    $getMissed = app(GetMissedMessages::class);

    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $outsider = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    expect(fn () => $getMissed->handle($outsider, $conversation))
        ->toThrow(UnauthorizedChatParticipantException::class);
});
