<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Actions\ApproveInspector;
use App\Domains\Inspector\Actions\RejectInspector;
use App\Domains\Inspector\Actions\ResubmitInspector;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('completes the full approval lifecycle and synchronizes users.status to active', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $inspectorUser = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Pending,
    ]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);

    // Attach all 4 required documents
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Ktp]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Certificate]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Cv]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Portfolio]);

    expect($profile->isReadyForVerification())->toBeTrue();

    // Admin approves
    $approveAction = app(ApproveInspector::class);
    $approveAction->handle($admin, $profile);

    // Profile verification_status is approved
    $freshProfile = $profile->fresh();
    expect($freshProfile->verification_status)->toBe(VerificationStatus::Approved)
        ->and($freshProfile->verified_by)->toBe($admin->id)
        ->and($freshProfile->verified_at)->not->toBeNull();

    // User status is automatically synchronized to active via listener
    $freshUser = $inspectorUser->fresh();
    expect($freshUser->status)->toBe(UserStatus::Active);
});

it('completes the rejection and resubmission lifecycle preserving rejection history', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $inspectorUser = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Pending,
    ]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);

    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Ktp]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Certificate]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Cv]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Portfolio]);

    // Admin rejects
    $rejectAction = app(RejectInspector::class);
    $rejectReason = 'Sertifikat tidak sesuai standar SIMOKAS.';
    $rejectAction->handle($admin, $profile, $rejectReason);

    $rejectedProfile = $profile->fresh();
    expect($rejectedProfile->verification_status)->toBe(VerificationStatus::Rejected)
        ->and($rejectedProfile->rejection_reason)->toBe($rejectReason)
        ->and($inspectorUser->fresh()->status)->toBe(UserStatus::Pending);

    // Inspector resubmits
    $resubmitAction = app(ResubmitInspector::class);
    $resubmitAction->handle($inspectorUser, $rejectedProfile);

    $resubmittedProfile = $profile->fresh();
    expect($resubmittedProfile->verification_status)->toBe(VerificationStatus::Pending)
        ->and($resubmittedProfile->rejection_reason)->toBeNull()
        ->and($resubmittedProfile->rejection_history)->toBeArray()
        ->and(count($resubmittedProfile->rejection_history))->toBe(1)
        ->and($resubmittedProfile->rejection_history[0]['reason'])->toBe($rejectReason)
        ->and($inspectorUser->fresh()->status)->toBe(UserStatus::Pending);
});
