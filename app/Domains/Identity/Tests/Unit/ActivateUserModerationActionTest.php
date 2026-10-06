<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Actions\Moderation\ActivateUser;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Events\UserActivated;
use App\Domains\Identity\Exceptions\UserModerationException;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('activates a blocked user and fires the event once', function () {
    $dispatched = 0;
    Event::listen(UserActivated::class, function () use (&$dispatched): void {
        $dispatched++;
    });

    $admin = User::factory()->admin()->active()->create();
    $user = User::factory()->user()->blocked()->create();

    app(ActivateUser::class)->handle($admin, (string) $user->id);

    expect($user->fresh()->status)->toBe(UserStatus::Active);
    expect($dispatched)->toBe(1);
});

it('activating twice applies once and fires the event once', function () {
    $dispatched = 0;
    Event::listen(UserActivated::class, function () use (&$dispatched): void {
        $dispatched++;
    });

    $admin = User::factory()->admin()->active()->create();
    $user = User::factory()->user()->blocked()->create();

    $action = app(ActivateUser::class);
    $action->handle($admin, (string) $user->id);
    $action->handle($admin, (string) $user->id);

    expect($user->fresh()->status)->toBe(UserStatus::Active);
    expect($dispatched)->toBe(1);
});

it('refuses to activate a pending user', function () {
    $admin = User::factory()->admin()->active()->create();
    $user = User::factory()->user()->pending()->create();

    expect(fn () => app(ActivateUser::class)->handle($admin, (string) $user->id))
        ->toThrow(UserModerationException::class);

    expect($user->fresh()->status)->toBe(UserStatus::Pending);
});

it('non-admin cannot activate', function () {
    $nonAdmin = User::factory()->user()->active()->create();
    $user = User::factory()->user()->blocked()->create();

    expect(fn () => app(ActivateUser::class)->handle($nonAdmin, (string) $user->id))
        ->toThrow(UserModerationException::class);

    expect($user->fresh()->status)->toBe(UserStatus::Blocked);
});
