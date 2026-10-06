<?php

declare(strict_types=1);

namespace App\Domains\Notification\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Notification\Livewire\NotificationBell;
use App\Domains\Notification\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('NTF-T05 shows the unread count on the bell', function (): void {
    $user = User::factory()->user()->create();
    Notification::factory()->forUser($user)->count(3)->create();
    Notification::factory()->forUser($user)->read()->create();

    actingAs($user);

    Livewire::test(NotificationBell::class)
        ->assertSee('3')
        ->assertSee(route('notifications.index'));
});

it('NTF-T05 hides the badge when there is nothing unread', function (): void {
    $user = User::factory()->user()->create();
    Notification::factory()->forUser($user)->read()->create();

    actingAs($user);

    Livewire::test(NotificationBell::class)
        ->assertDontSee('bg-danger');
});

it('NTF-T05 does not show the bell to guests', function (): void {
    Livewire::test(NotificationBell::class)
        ->assertDontSee(route('notifications.index'));
});
