<?php

declare(strict_types=1);

use App\Domains\Chat\Actions\SendMessage;
use App\Domains\Chat\Events\MessageSent;
use App\Domains\Chat\Exceptions\BlockedUserCannotChatException;
use App\Domains\Chat\Exceptions\InvalidMessageException;
use App\Domains\Chat\Exceptions\MessageRateLimitExceededException;
use App\Domains\Chat\Exceptions\UnauthorizedChatParticipantException;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('CHT-T02 saves message and dispatches MessageSent to correct channel', function () {
    Event::fake([MessageSent::class]);

    $action = app(SendMessage::class);

    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    $message = $action->handle($buyer, $conversation, 'Halo, apakah mobil masih ada?');

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->body)->toBe('Halo, apakah mobil masih ada?')
        ->and($message->sender_id)->toBe($buyer->id)
        ->and($message->conversation_id)->toBe($conversation->id);

    expect(Message::query()->count())->toBe(1);

    Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($conversation, $message) {
        $channels = $event->broadcastOn();
        expect($channels)->toHaveCount(1)
            ->and($channels[0]->name)->toBe('private-conversation.'.$conversation->id)
            ->and($event->broadcastAs())->toBe('message.sent')
            ->and($event->message->id)->toBe($message->id);

        return true;
    });
});

it('CHT-T04 rejects empty message or message exceeding 1000 characters (CHT-E4)', function (string $body, string $expectedReason) {
    $action = app(SendMessage::class);

    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    expect(fn () => $action->handle($buyer, $conversation, $body))
        ->toThrow(InvalidMessageException::class);
})->with([
    'empty string' => ['', 'empty'],
    'whitespace only' => ["   \n  \t ", 'empty'],
    '1001 characters' => [str_repeat('a', 1001), 'too_long'],
]);

it('CHT-T08 deduplicates double send with the same client key (CHT-E6)', function () {
    $action = app(SendMessage::class);

    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();
    $clientKey = (string) Str::uuid();

    // First send
    $message1 = $action->handle($buyer, $conversation, 'Pesan dengan kunci klien', $clientKey);

    // Second send with exact same client key (double click simulation)
    $message2 = $action->handle($buyer, $conversation, 'Pesan dengan kunci klien', $clientKey);

    expect($message2->id)->toBe($message1->id);
    expect(Message::query()->count())->toBe(1);
});

it('CHT-T08 rejects client key UUID that is already used by another conversation or sender', function () {
    $action = app(SendMessage::class);

    $user1 = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $user2 = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $user3 = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation1 = Conversation::factory()->withMembers($user1, $user2)->create();
    $conversation2 = Conversation::factory()->withMembers($user2, $user3)->create();

    $clientKey = (string) Str::uuid();

    // User1 sends in conversation1 with clientKey
    $action->handle($user1, $conversation1, 'Pesan pertama', $clientKey);

    // Scenario A: Different conversation tries to use same clientKey UUID
    expect(fn () => $action->handle($user2, $conversation2, 'Pesan kedua', $clientKey))
        ->toThrow(InvalidMessageException::class);

    // Scenario B: Different sender in same conversation tries to use same clientKey UUID
    expect(fn () => $action->handle($user2, $conversation1, 'Pesan ketiga', $clientKey))
        ->toThrow(InvalidMessageException::class);
});

it('CHT-T08 catches unique constraint violation and recovers existing message for same sender and conversation', function () {
    Event::fake([MessageSent::class]);

    $action = app(SendMessage::class);

    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    $clientKey = (string) Str::uuid();

    $existing = Message::query()->create([
        'id' => $clientKey,
        'conversation_id' => $conversation->id,
        'sender_id' => $buyer->id,
        'body' => 'Pesan balapan',
    ]);

    // Clear cache to simulate second concurrent thread that missed cache
    Cache::flush();

    // Now if the lookup in SendMessage was bypassed or raced, it should catch UniqueConstraintViolationException
    $result = $action->handle($buyer, $conversation, 'Pesan balapan', $clientKey);

    expect($result->id)->toBe($existing->id);
    expect(Message::query()->count())->toBe(1);
    Event::assertNotDispatched(MessageSent::class);
});


it('CHT-T10 rejects sending message by blocked user (CHT-R6)', function () {
    $action = app(SendMessage::class);

    $blockedBuyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Blocked]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($blockedBuyer, $seller)->create();

    expect(fn () => $action->handle($blockedBuyer, $conversation, 'Pesan dari akun terblokir'))
        ->toThrow(BlockedUserCannotChatException::class);
});

it('CHT-T12 throttles message sending when rate limit is exceeded (CHT-R9)', function () {
    $action = app(SendMessage::class);

    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    RateLimiter::clear('chat_send_rate:'.$buyer->id);

    // Send 30 messages (allowed)
    for ($i = 0; $i < 30; $i++) {
        $action->handle($buyer, $conversation, "Pesan nomor {$i}");
    }

    // 31st message: should be blocked by rate limit
    expect(fn () => $action->handle($buyer, $conversation, 'Pesan melebihi batas'))
        ->toThrow(MessageRateLimitExceededException::class);
});

it('rejects outsider who is not a member of conversation (CHT-R3)', function () {
    $action = app(SendMessage::class);

    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $outsider = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    expect(fn () => $action->handle($outsider, $conversation, 'Pesan dari orang luar'))
        ->toThrow(UnauthorizedChatParticipantException::class);
});
