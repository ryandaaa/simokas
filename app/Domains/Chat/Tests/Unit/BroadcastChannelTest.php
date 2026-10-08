<?php

declare(strict_types=1);

namespace App\Domains\Chat\Tests\Unit;

use App\Domains\Chat\Broadcasting\ConversationChannel;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('CHT-T03 allows channel subscription for members and rejects outsiders', function () {
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $outsider = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    $channel = app(ConversationChannel::class);

    expect($channel->join($buyer, (string) $conversation->getKey()))->toBeTrue()
        ->and($channel->join($seller, (string) $conversation->getKey()))->toBeTrue()
        ->and($channel->join($outsider, (string) $conversation->getKey()))->toBeFalse()
        ->and($channel->join(null, (string) $conversation->getKey()))->toBeFalse();
});

it('CHT-T03 rejects channel subscription for blocked members', function () {
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $blocked = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Blocked]);

    $conversation = Conversation::factory()->withMembers($buyer, $blocked)->create();

    expect(app(ConversationChannel::class)->join($blocked, (string) $conversation->getKey()))->toBeFalse();
});
