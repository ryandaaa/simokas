<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Listeners\ActivateApprovedInspector;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Events\InspectorApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('activates user status when InspectorApproved event is received', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $inspectorUser = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Pending,
    ]);

    $event = new InspectorApproved(
        inspectorProfileId: 'test-profile-id',
        userId: $inspectorUser->id,
        verifiedBy: $admin->id,
    );

    $listener = app(ActivateApprovedInspector::class);
    $listener->handle($event);

    expect($inspectorUser->fresh()->status)->toBe(UserStatus::Active);
});
