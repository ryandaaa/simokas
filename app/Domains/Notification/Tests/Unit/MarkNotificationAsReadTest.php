<?php

declare(strict_types=1);

namespace App\Domains\Notification\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Notification\Actions\MarkAllNotificationsAsRead;
use App\Domains\Notification\Actions\MarkNotificationAsRead;
use App\Domains\Notification\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('NTF-T04 marks a single notification as read', function (): void {
    $user = User::factory()->user()->create();
    $notification = Notification::factory()->forUser($user)->create();

    $result = app(MarkNotificationAsRead::class)->handle($notification);

    expect($result->isRead())->toBeTrue();
});

it('NTF-T04 marking as read is idempotent', function (): void {
    $user = User::factory()->user()->create();
    $notification = Notification::factory()->forUser($user)->create();
    $action = app(MarkNotificationAsRead::class);

    $first = $action->handle($notification);
    $second = $action->handle($first);

    expect($first->read_at->equalTo($second->read_at))->toBeTrue();
});

it('NTF-T04 marks all notifications of the user as read', function (): void {
    $user = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    Notification::factory()->forUser($user)->count(3)->create();
    Notification::factory()->forUser($user)->read()->create();
    Notification::factory()->forUser($other)->count(2)->create();

    $marked = app(MarkAllNotificationsAsRead::class)->handle($user);

    expect($marked)->toBe(3)
        ->and(Notification::query()->forUser($user->getKey())->unread()->count())->toBe(0)
        ->and(Notification::query()->forUser($other->getKey())->unread()->count())->toBe(2);
});
