<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Contracts\InspectorEligibility;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('ONB-T11 provides inspector eligibility and allowed specialties matching profile (ONB-R5)', function () {
    $service = app(InspectorEligibility::class);

    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'vehicle_types' => ['car', 'motorcycle'],
        'verification_status' => VerificationStatus::Approved,
    ]);

    expect($service->canViewAndTakeJobs($user))->toBeTrue()
        ->and($service->getAllowedSpecialties($user))->toEqual(['car', 'motorcycle']);
});

it('non-approved inspectors cannot view or take jobs and have empty allowed specialties (ONB-R3)', function (VerificationStatus $status) {
    $service = app(InspectorEligibility::class);

    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Pending,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'vehicle_types' => ['car'],
        'verification_status' => $status,
    ]);

    expect($service->canViewAndTakeJobs($user))->toBeFalse()
        ->and($service->getAllowedSpecialties($user))->toBeEmpty();
})->with([
    'pending' => VerificationStatus::Pending,
    'rejected' => VerificationStatus::Rejected,
]);

it('blocked inspectors cannot view or take jobs even if their profile was approved (ONB-E8)', function () {
    $service = app(InspectorEligibility::class);

    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Blocked,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'vehicle_types' => ['car'],
        'verification_status' => VerificationStatus::Approved,
    ]);

    expect($service->canViewAndTakeJobs($user))->toBeFalse()
        ->and($service->getAllowedSpecialties($user))->toBeEmpty();
});
