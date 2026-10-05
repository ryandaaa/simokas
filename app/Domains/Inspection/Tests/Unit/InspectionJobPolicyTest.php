<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
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
        'status' => JobStatus::Taken,
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

it('JOB-T18 tests schedule authorization: only assigned inspector on taken status can schedule', function (string $roleType, JobStatus $status, bool $expected) {
    $assignedInspector = User::factory()->inspector()->create();
    $requester = User::factory()->user()->create();

    $job = InspectionJob::factory()->create([
        'requester_id' => $requester->id,
        'inspector_id' => $assignedInspector->id,
        'status' => $status,
    ]);

    $actingUser = match ($roleType) {
        'assigned_inspector' => $assignedInspector,
        'other_inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
        'requester' => $requester,
        'other_user' => User::factory()->user()->create(),
        'blocked_inspector' => tap($assignedInspector, fn ($u) => $u->update(['status' => 'blocked'])),
        default => throw new \InvalidArgumentException,
    };

    $policy = new InspectionJobPolicy;
    expect($policy->schedule($actingUser, $job))->toBe($expected);
})->with([
    'assigned inspector on taken' => ['assigned_inspector', JobStatus::Taken, true],
    'assigned inspector on scheduled' => ['assigned_inspector', JobStatus::Scheduled, false],
    'assigned inspector on in_progress' => ['assigned_inspector', JobStatus::InProgress, false],
    'other inspector on taken' => ['other_inspector', JobStatus::Taken, false],
    'admin on taken' => ['admin', JobStatus::Taken, false],
    'requester on taken' => ['requester', JobStatus::Taken, false],
    'other user on taken' => ['other_user', JobStatus::Taken, false],
    'blocked inspector on taken' => ['blocked_inspector', JobStatus::Taken, false],
]);

it('JOB-T18 tests start authorization: only assigned inspector on scheduled status can start', function (string $roleType, JobStatus $status, bool $expected) {
    $assignedInspector = User::factory()->inspector()->create();
    $requester = User::factory()->user()->create();

    $job = InspectionJob::factory()->create([
        'requester_id' => $requester->id,
        'inspector_id' => $assignedInspector->id,
        'status' => $status,
    ]);

    $actingUser = match ($roleType) {
        'assigned_inspector' => $assignedInspector,
        'other_inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
        'requester' => $requester,
        'blocked_inspector' => tap($assignedInspector, fn ($u) => $u->update(['status' => 'blocked'])),
        default => throw new \InvalidArgumentException,
    };

    $policy = new InspectionJobPolicy;
    expect($policy->start($actingUser, $job))->toBe($expected);
})->with([
    'assigned inspector on scheduled' => ['assigned_inspector', JobStatus::Scheduled, true],
    'assigned inspector on taken' => ['assigned_inspector', JobStatus::Taken, false],
    'assigned inspector on in_progress' => ['assigned_inspector', JobStatus::InProgress, false],
    'other inspector on scheduled' => ['other_inspector', JobStatus::Scheduled, false],
    'admin on scheduled' => ['admin', JobStatus::Scheduled, false],
    'requester on scheduled' => ['requester', JobStatus::Scheduled, false],
    'blocked inspector on scheduled' => ['blocked_inspector', JobStatus::Scheduled, false],
]);

it('JOB-T18 tests cancelAsInspector: only assigned inspector on taken or scheduled status', function (string $roleType, JobStatus $status, bool $expected) {
    $assignedInspector = User::factory()->inspector()->create();
    $requester = User::factory()->user()->create();

    $job = InspectionJob::factory()->create([
        'requester_id' => $requester->id,
        'inspector_id' => $assignedInspector->id,
        'status' => $status,
    ]);

    $actingUser = match ($roleType) {
        'assigned_inspector' => $assignedInspector,
        'other_inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
        'requester' => $requester,
        'blocked_inspector' => tap($assignedInspector, fn ($u) => $u->update(['status' => 'blocked'])),
        default => throw new \InvalidArgumentException,
    };

    $policy = new InspectionJobPolicy;
    expect($policy->cancelAsInspector($actingUser, $job))->toBe($expected);
})->with([
    'assigned inspector on taken' => ['assigned_inspector', JobStatus::Taken, true],
    'assigned inspector on scheduled' => ['assigned_inspector', JobStatus::Scheduled, true],
    'assigned inspector on in_progress' => ['assigned_inspector', JobStatus::InProgress, false],
    'other inspector on taken' => ['other_inspector', JobStatus::Taken, false],
    'admin on taken' => ['admin', JobStatus::Taken, false],
    'requester on taken' => ['requester', JobStatus::Taken, false],
    'blocked inspector on taken' => ['blocked_inspector', JobStatus::Taken, false],
]);

it('JOB-T18 tests cancelAsRequester: only owner requester before in_progress', function (string $roleType, JobStatus $status, bool $expected) {
    $requester = User::factory()->user()->create();
    $assignedInspector = User::factory()->inspector()->create();

    $job = InspectionJob::factory()->create([
        'requester_id' => $requester->id,
        'inspector_id' => $assignedInspector->id,
        'status' => $status,
    ]);

    $actingUser = match ($roleType) {
        'owner' => $requester,
        'other_user' => User::factory()->user()->create(),
        'inspector' => $assignedInspector,
        'admin' => User::factory()->admin()->create(),
        'blocked_owner' => tap($requester, fn ($u) => $u->update(['status' => 'blocked'])),
        default => throw new \InvalidArgumentException,
    };

    $policy = new InspectionJobPolicy;
    expect($policy->cancelAsRequester($actingUser, $job))->toBe($expected);
})->with([
    'owner on requested' => ['owner', JobStatus::Requested, true],
    'owner on paid' => ['owner', JobStatus::Paid, true],
    'owner on taken' => ['owner', JobStatus::Taken, true],
    'owner on scheduled' => ['owner', JobStatus::Scheduled, true],
    'owner on in_progress (rejected)' => ['owner', JobStatus::InProgress, false],
    'owner on result_submitted (rejected)' => ['owner', JobStatus::ResultSubmitted, false],
    'owner on completed (rejected)' => ['owner', JobStatus::Completed, false],
    'other user on paid' => ['other_user', JobStatus::Paid, false],
    'inspector on paid' => ['inspector', JobStatus::Paid, false],
    'admin on paid' => ['admin', JobStatus::Paid, false],
    'blocked owner on paid' => ['blocked_owner', JobStatus::Paid, false],
]);

it('JOB-T18 tests cancelAsAdmin: only admin on active jobs', function (string $roleType, JobStatus $status, bool $expected) {
    $admin = User::factory()->admin()->create();
    $requester = User::factory()->user()->create();

    $job = InspectionJob::factory()->create([
        'requester_id' => $requester->id,
        'status' => $status,
    ]);

    $actingUser = match ($roleType) {
        'admin' => $admin,
        'user' => $requester,
        'inspector' => User::factory()->inspector()->create(),
        'blocked_admin' => tap($admin, fn ($u) => $u->update(['status' => 'blocked'])),
        default => throw new \InvalidArgumentException,
    };

    $policy = new InspectionJobPolicy;
    expect($policy->cancelAsAdmin($actingUser, $job))->toBe($expected);
})->with([
    'admin on requested' => ['admin', JobStatus::Requested, true],
    'admin on paid' => ['admin', JobStatus::Paid, true],
    'admin on in_progress' => ['admin', JobStatus::InProgress, true],
    'admin on completed (final)' => ['admin', JobStatus::Completed, false],
    'admin on cancelled (final)' => ['admin', JobStatus::Cancelled, false],
    'user on in_progress' => ['user', JobStatus::InProgress, false],
    'inspector on in_progress' => ['inspector', JobStatus::InProgress, false],
    'blocked admin on in_progress' => ['blocked_admin', JobStatus::InProgress, false],
]);
