<?php

declare(strict_types=1);

namespace App\Domains\Notification\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Notification\Livewire\NotificationIndex;
use App\Domains\Notification\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('NTF-T03 redirects guests to login', function (): void {
    get(route('notifications.index'))->assertRedirect(route('login'));
});

it('NTF-T05 shows only the user own notifications', function (): void {
    $user = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    $mine = Notification::factory()->forUser($user)->create(['title' => 'Milik saya']);
    Notification::factory()->forUser($other)->create(['title' => 'Milik orang lain']);

    actingAs($user);

    Livewire::test(NotificationIndex::class)
        ->assertSee('Milik saya')
        ->assertDontSee('Milik orang lain');

    get(route('notifications.index'))->assertOk();
});

it('NTF-T04 opening a notification marks it as read and redirects to its link', function (): void {
    $user = User::factory()->user()->create();
    $notification = Notification::factory()->forUser($user)->create(['link' => route('dashboard.user')]);

    actingAs($user);

    Livewire::test(NotificationIndex::class)
        ->call('openNotification', $notification->getKey())
        ->assertRedirect(route('dashboard.user'));

    expect($notification->refresh()->isRead())->toBeTrue();
});

it('NTF-T03 opening someone else notification is forbidden without side effects', function (): void {
    $user = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    $notification = Notification::factory()->forUser($other)->create();

    actingAs($user);

    Livewire::test(NotificationIndex::class)
        ->call('openNotification', $notification->getKey())
        ->assertForbidden();

    expect($notification->refresh()->isRead())->toBeFalse();
});

it('NTF-T04 marking all as read clears the unread count', function (): void {
    $user = User::factory()->user()->create();
    Notification::factory()->forUser($user)->count(4)->create();

    actingAs($user);

    Livewire::test(NotificationIndex::class)
        ->call('markAllAsRead')
        ->assertSee(__('notification.index.empty_title'));

    expect(Notification::query()->forUser($user->getKey())->unread()->count())->toBe(0);
});

it('NTF-T05 shows an empty state when there are no notifications', function (): void {
    $user = User::factory()->user()->create();

    actingAs($user);

    Livewire::test(NotificationIndex::class)
        ->assertSee(__('notification.index.empty_title'))
        ->assertSee(__('notification.index.empty_description'));
});
