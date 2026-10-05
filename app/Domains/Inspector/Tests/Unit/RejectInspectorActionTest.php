<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Actions\RejectInspector;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Events\InspectorRejected;
use App\Domains\Inspector\Exceptions\InvalidInspectorProfileException;
use App\Domains\Inspector\Exceptions\InvalidVerificationStatusTransitionException;
use App\Domains\Inspector\Exceptions\MissingRejectionReasonException;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('ONB-T05 admin rejects pending inspector with reason successfully, but fails without reason', function () {
    Event::fake([InspectorRejected::class]);

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $inspectorUser = User::factory()->create(['role' => UserRole::Inspector]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
        'rejection_reason' => null,
    ]);

    $action = app(RejectInspector::class);

    // Empty reason must fail
    expect(fn () => $action->handle($admin, $profile, ''))
        ->toThrow(MissingRejectionReasonException::class);

    // Whitespace-only reason must fail
    expect(fn () => $action->handle($admin, $profile, '   '))
        ->toThrow(MissingRejectionReasonException::class);

    // Valid non-empty reason succeeds
    $reason = 'Foto KTP tidak terbaca dan sertifikat kadaluwarsa.';
    $result = $action->handle($admin, $profile, $reason);

    expect($result->verification_status)->toBe(VerificationStatus::Rejected)
        ->and($result->rejection_reason)->toBe($reason);

    $fresh = $profile->fresh();
    expect($fresh->verification_status)->toBe(VerificationStatus::Rejected)
        ->and($fresh->rejection_reason)->toBe($reason);

    Event::assertDispatched(InspectorRejected::class, function (InspectorRejected $event) use ($profile, $inspectorUser, $admin, $reason) {
        return $event->inspectorProfileId === $profile->id
            && $event->userId === $inspectorUser->id
            && $event->rejectedBy === $admin->id
            && $event->reason === $reason;
    });
});

it('fails to reject when caller is not an admin', function () {
    $regularUser = User::factory()->create(['role' => UserRole::User]);
    $inspectorUser = User::factory()->create(['role' => UserRole::Inspector]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);

    $action = app(RejectInspector::class);

    expect(fn () => $action->handle($regularUser, $profile, 'Alasan penolakan'))
        ->toThrow(InvalidInspectorProfileException::class);
});

it('ONB-T10 double rejection or action on already rejected profile fails cleanly without double effect (ONB-E6)', function () {
    $admin1 = User::factory()->create(['role' => UserRole::Admin]);
    $admin2 = User::factory()->create(['role' => UserRole::Admin]);
    $inspectorUser = User::factory()->create(['role' => UserRole::Inspector]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);

    $action = app(RejectInspector::class);

    $action->handle($admin1, $profile, 'Alasan pertama');

    expect(fn () => $action->handle($admin2, $profile, 'Alasan kedua'))
        ->toThrow(InvalidVerificationStatusTransitionException::class);
});
