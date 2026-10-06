<?php

declare(strict_types=1);

namespace App\Domains\Notification\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Notification\Actions\StoreNotification;
use App\Domains\Notification\Enums\NotificationType;
use App\Domains\Notification\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('NTF-T01 stores a notification for the recipient', function (): void {
    $user = User::factory()->user()->create();

    $notification = app(StoreNotification::class)->handle(
        userId: $user->getKey(),
        type: NotificationType::JobTaken,
        title: 'Job diambil',
        body: 'Job inspeksimu diambil.',
        link: '/jobs/abc',
        dedupeKey: 'job_taken:abc:'.$user->getKey(),
    );

    expect($notification)->toBeInstanceOf(Notification::class)
        ->and($notification->user_id)->toBe($user->getKey())
        ->and($notification->type)->toBe(NotificationType::JobTaken)
        ->and($notification->read_at)->toBeNull();
});

it('NTF-T02 does not create a duplicate notification for the same dedupe key', function (): void {
    $user = User::factory()->user()->create();
    $store = app(StoreNotification::class);

    $first = $store->handle(
        userId: $user->getKey(),
        type: NotificationType::JobTaken,
        title: 'Job diambil',
        body: 'Pertama.',
        link: null,
        dedupeKey: 'job_taken:abc:'.$user->getKey(),
    );

    $second = $store->handle(
        userId: $user->getKey(),
        type: NotificationType::JobTaken,
        title: 'Job diambil',
        body: 'Kedua.',
        link: null,
        dedupeKey: 'job_taken:abc:'.$user->getKey(),
    );

    expect(Notification::query()->count())->toBe(1)
        ->and($second->getKey())->toBe($first->getKey())
        ->and($second->body)->toBe('Pertama.');
});

it('NTF-T09 skips blocked recipients without error', function (): void {
    $blocked = User::factory()->user()->blocked()->create();

    $result = app(StoreNotification::class)->handle(
        userId: $blocked->getKey(),
        type: NotificationType::JobTaken,
        title: 'Job diambil',
        body: 'Tidak boleh masuk.',
        link: null,
        dedupeKey: 'job_taken:abc:'.$blocked->getKey(),
    );

    expect($result)->toBeNull()
        ->and(Notification::query()->count())->toBe(0);
});

it('NTF-E3 skips missing recipients without error', function (): void {
    $result = app(StoreNotification::class)->handle(
        userId: '00000000-0000-0000-0000-000000000000',
        type: NotificationType::JobTaken,
        title: 'Job diambil',
        body: 'Tidak boleh masuk.',
        link: null,
        dedupeKey: 'job_taken:abc:missing',
    );

    expect($result)->toBeNull()
        ->and(Notification::query()->count())->toBe(0);
});

it('NTF-T10 groups consecutive chat messages into one notification', function (): void {
    $user = User::factory()->user()->create();
    $store = app(StoreNotification::class);

    $store->handleChatMessage($user->getKey(), 'conv-1', 'Budi', '/conversations/conv-1');
    $grouped = $store->handleChatMessage($user->getKey(), 'conv-1', 'Budi', '/conversations/conv-1');

    expect(Notification::query()->count())->toBe(1)
        ->and($grouped->data['count'])->toBe(2)
        ->and($grouped->body)->toContain('2')
        ->and($grouped->isRead())->toBeFalse();
});

it('NTF-T10 reuses the row as unread when new messages arrive after reading', function (): void {
    $user = User::factory()->user()->create();
    $store = app(StoreNotification::class);

    $store->handleChatMessage($user->getKey(), 'conv-1', 'Budi', '/conversations/conv-1');

    /** @var Notification $read */
    $read = Notification::query()->firstOrFail();
    $read->update(['read_at' => now()]);

    $reused = $store->handleChatMessage($user->getKey(), 'conv-1', 'Budi', '/conversations/conv-1');

    expect(Notification::query()->count())->toBe(1)
        ->and($reused->getKey())->toBe($read->getKey())
        ->and($reused->isRead())->toBeFalse()
        ->and($reused->data['count'])->toBe(1);
});

it('NTF-T10 keeps separate notifications per conversation', function (): void {
    $user = User::factory()->user()->create();
    $store = app(StoreNotification::class);

    $store->handleChatMessage($user->getKey(), 'conv-1', 'Budi', '/conversations/conv-1');
    $store->handleChatMessage($user->getKey(), 'conv-2', 'Siti', '/conversations/conv-2');

    expect(Notification::query()->count())->toBe(2);
});
