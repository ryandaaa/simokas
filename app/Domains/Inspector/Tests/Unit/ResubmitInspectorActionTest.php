<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Actions\ResubmitInspector;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Exceptions\InvalidInspectorProfileException;
use App\Domains\Inspector\Exceptions\InvalidVerificationStatusTransitionException;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('ONB-T06 rejected inspector can resubmit, status returns to pending and previous reason saved to history (ONB-E5)', function () {
    $inspectorUser = User::factory()->create(['role' => UserRole::Inspector]);

    $originalReason = 'Foto KTP buram.';
    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Rejected,
        'rejection_reason' => $originalReason,
        'rejection_history' => null,
    ]);

    $action = app(ResubmitInspector::class);
    $result = $action->handle($inspectorUser, $profile);

    expect($result->verification_status)->toBe(VerificationStatus::Pending)
        ->and($result->rejection_reason)->toBeNull()
        ->and($result->rejection_history)->toBeArray()
        ->and(count($result->rejection_history))->toBe(1)
        ->and($result->rejection_history[0]['reason'])->toBe($originalReason);

    $fresh = $profile->fresh();
    expect($fresh->verification_status)->toBe(VerificationStatus::Pending)
        ->and($fresh->rejection_reason)->toBeNull()
        ->and(count($fresh->rejection_history))->toBe(1)
        ->and($fresh->rejection_history[0]['reason'])->toBe($originalReason);
});

it('cannot resubmit a profile that is not currently rejected', function () {
    $inspectorUser = User::factory()->create(['role' => UserRole::Inspector]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);

    $action = app(ResubmitInspector::class);

    expect(fn () => $action->handle($inspectorUser, $profile))
        ->toThrow(InvalidVerificationStatusTransitionException::class);
});

it('prevents another user from resubmitting an inspector profile', function () {
    $otherUser = User::factory()->create(['role' => UserRole::Inspector]);
    $ownerUser = User::factory()->create(['role' => UserRole::Inspector]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $ownerUser->id,
        'verification_status' => VerificationStatus::Rejected,
        'rejection_reason' => 'Perbaiki berkas',
    ]);

    $action = app(ResubmitInspector::class);

    expect(fn () => $action->handle($otherUser, $profile))
        ->toThrow(InvalidInspectorProfileException::class);
});

it('fails on concurrent or duplicate resubmit when status is no longer rejected', function () {
    $inspectorUser = User::factory()->create(['role' => UserRole::Inspector]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Rejected,
        'rejection_reason' => 'Perbaiki berkas',
    ]);

    $action = app(ResubmitInspector::class);

    $action->handle($inspectorUser, $profile);

    expect(fn () => $action->handle($inspectorUser, $profile))
        ->toThrow(InvalidVerificationStatusTransitionException::class);
});
