<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspection\Policies\InspectionJobPolicy;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('JOB-T18 tests request authorization matrix per role', function (UserRole $role, bool $isBlocked, bool $expected) {
    $user = User::factory()->create([
        'role' => $role,
        'status' => $isBlocked ? 'blocked' : 'active',
    ]);
    $listing = Listing::factory()->active()->create();

    $policy = new InspectionJobPolicy;
    expect($policy->request($user, $listing))->toBe($expected);
})->with([
    'active user' => [UserRole::User, false, true],
    'active inspector' => [UserRole::Inspector, false, false],
    'active admin' => [UserRole::Admin, false, false],
    'blocked user' => [UserRole::User, true, false],
    'blocked inspector' => [UserRole::Inspector, true, false],
    'blocked admin' => [UserRole::Admin, true, false],
]);

it('JOB-T18 allows only requester owner to view job; all other roles get 403', function (string $roleType, bool $isOwner, bool $expected) {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create();
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $owner->id,
    ]);

    $actingUser = match ($roleType) {
        'owner' => $owner,
        'other_user' => User::factory()->user()->create(),
        'inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
        'blocked_owner' => tap($owner, fn ($u) => $u->update(['status' => 'blocked'])),
        default => throw new \InvalidArgumentException("Unknown role type: {$roleType}"),
    };

    $policy = new InspectionJobPolicy;
    expect($policy->view($actingUser, $job))->toBe($expected);
})->with([
    'owner user' => ['owner', true, true],
    'other user' => ['other_user', false, false],
    'inspector' => ['inspector', false, false],
    'admin' => ['admin', false, false],
    'blocked owner' => ['blocked_owner', true, false],
]);

it('JOB-T18 allows only assigned inspector and admin to viewDetail and viewPrivate; requester and other roles get false', function (string $roleType, bool $expected) {
    $assignedInspector = User::factory()->inspector()->create();
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create();
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'inspector_id' => $assignedInspector->id,
        'status' => \App\Domains\Inspection\Enums\JobStatus::Taken,
    ]);

    $actingUser = match ($roleType) {
        'assigned_inspector' => $assignedInspector,
        'other_inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
        'requester' => $requester,
        'other_user' => User::factory()->user()->create(),
        'blocked_inspector' => tap($assignedInspector, fn ($u) => $u->update(['status' => 'blocked'])),
        'blocked_admin' => tap(User::factory()->admin()->create(), fn ($u) => $u->update(['status' => 'blocked'])),
        default => throw new \InvalidArgumentException("Unknown role type: {$roleType}"),
    };

    $policy = new InspectionJobPolicy;
    expect($policy->viewDetail($actingUser, $job))->toBe($expected)
        ->and($policy->viewPrivate($actingUser, $job))->toBe($expected);
})->with([
    'assigned inspector' => ['assigned_inspector', true],
    'other inspector' => ['other_inspector', false],
    'admin' => ['admin', true],
    'requester' => ['requester', false],
    'other user' => ['other_user', false],
    'blocked inspector' => ['blocked_inspector', false],
    'blocked admin' => ['blocked_admin', false],
]);

it('JOB-T18 allows only active inspector to take job; other roles are rejected', function (string $roleType, bool $expected) {
    $inspector = User::factory()->inspector()->create();
    $listing = Listing::factory()->active()->create();
    $job = InspectionJob::factory()->paid()->create(['listing_id' => $listing->id]);

    $actingUser = match ($roleType) {
        'inspector' => $inspector,
        'user' => User::factory()->user()->create(),
        'admin' => User::factory()->admin()->create(),
        'blocked_inspector' => tap($inspector, fn ($u) => $u->update(['status' => 'blocked'])),
        default => throw new \InvalidArgumentException("Unknown role type: {$roleType}"),
    };

    $policy = new InspectionJobPolicy;
    expect($policy->take($actingUser, $job))->toBe($expected);
})->with([
    'active inspector' => ['inspector', true],
    'user' => ['user', false],
    'admin' => ['admin', false],
    'blocked inspector' => ['blocked_inspector', false],
]);

