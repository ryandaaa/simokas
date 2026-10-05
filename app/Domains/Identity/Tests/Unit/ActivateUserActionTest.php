<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Actions\ActivateUser;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('activates a pending user into active status', function () {
    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Pending,
    ]);

    $action = app(ActivateUser::class);
    $result = $action->handle($user->id);

    expect($result->status)->toBe(UserStatus::Active);

    $fresh = $user->fresh();
    expect($fresh->status)->toBe(UserStatus::Active);
});

it('leaves already active user in active status idempotently', function () {
    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    $action = app(ActivateUser::class);
    $result = $action->handle($user->id);

    expect($result->status)->toBe(UserStatus::Active);
});
