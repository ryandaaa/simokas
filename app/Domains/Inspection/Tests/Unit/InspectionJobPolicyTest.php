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
