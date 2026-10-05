<?php

declare(strict_types=1);

use App\Domains\Chat\Actions\MarkConversationAsRead;
use App\Domains\Chat\Actions\SendMessage;
use App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\travel;

uses(TestCase::class, RefreshDatabase::class);

it('CHT-T06 increments unread count for recipient and resets to zero after marked as read (last_read_at)', function () {
    $sendMessage = app(SendMessage::class);
    $markAsRead = app(MarkConversationAsRead::class);

    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    // Initial state: 0 unread for both
    expect($conversation->unreadCountFor($buyer))->toBe(0)
        ->and($conversation->unreadCountFor($seller))->toBe(0);

    // Buyer sends first message
    $sendMessage->handle($buyer, $conversation, 'Pesan 1 dari pembeli');

    expect($conversation->unreadCountFor($buyer))->toBe(0)
        ->and($conversation->unreadCountFor($seller))->toBe(1);

    // Travel time forward by 1 second and send second message
    travel(1)->seconds();
    $sendMessage->handle($buyer, $conversation, 'Pesan 2 dari pembeli');

    expect($conversation->unreadCountFor($seller))->toBe(2);

    // Seller opens conversation and marks as read
    travel(1)->seconds();
    $markAsRead->handle($seller, $conversation);

    // Seller unread count resets to 0
    expect($conversation->unreadCountFor($seller))->toBe(0);

    // Seller sends a message back
    $sendMessage->handle($seller, $conversation, 'Pesan balasan penjual');

    expect($conversation->unreadCountFor($seller))->toBe(0)
        ->and($conversation->unreadCountFor($buyer))->toBe(1);

    // Buyer marks as read
    $markAsRead->handle($buyer, $conversation);
    expect($conversation->unreadCountFor($buyer))->toBe(0);
});

it('rejects outsider from marking conversation as read', function () {
    $markAsRead = app(MarkConversationAsRead::class);

    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $outsider = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    expect(fn () => $markAsRead->handle($outsider, $conversation))
        ->toThrow(UnauthorizedChatParticipantException::class);
});
