<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Actions\ApproveInspector;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Events\InspectorApproved;
use App\Domains\Inspector\Exceptions\IncompleteInspectorDocumentsException;
use App\Domains\Inspector\Exceptions\InvalidInspectorProfileException;
use App\Domains\Inspector\Exceptions\InvalidVerificationStatusTransitionException;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('ONB-T04 admin approves pending inspector with complete documents successfully', function () {
    Event::fake([InspectorApproved::class]);

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $inspectorUser = User::factory()->create(['role' => UserRole::Inspector]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
        'verified_by' => null,
        'verified_at' => null,
    ]);

    // Attach all 4 required documents
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Ktp]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Certificate]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Cv]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Portfolio]);

    $action = app(ApproveInspector::class);
    $result = $action->handle($admin, $profile);

    expect($result->verification_status)->toBe(VerificationStatus::Approved)
        ->and($result->verified_by)->toBe($admin->id)
        ->and($result->verified_at)->not->toBeNull();

    $fresh = $profile->fresh();
    expect($fresh->verification_status)->toBe(VerificationStatus::Approved)
        ->and($fresh->verified_by)->toBe($admin->id)
        ->and($fresh->verified_at)->not->toBeNull();

    Event::assertDispatched(InspectorApproved::class, function (InspectorApproved $event) use ($profile, $inspectorUser, $admin) {
        return $event->inspectorProfileId === $profile->id
            && $event->userId === $inspectorUser->id
            && $event->verifiedBy === $admin->id;
    });
});

it('fails to approve when caller is not an admin', function () {
    $regularUser = User::factory()->create(['role' => UserRole::User]);
    $inspectorUser = User::factory()->create(['role' => UserRole::Inspector]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);

    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Ktp]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Certificate]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Cv]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Portfolio]);

    $action = app(ApproveInspector::class);

    expect(fn () => $action->handle($regularUser, $profile))
        ->toThrow(InvalidInspectorProfileException::class);
});

it('cannot approve an inspector submission with incomplete required documents (ONB-E7)', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $inspectorUser = User::factory()->create(['role' => UserRole::Inspector]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);

    // Only upload KTP, missing Certificate, CV, Portfolio
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Ktp]);

    $action = app(ApproveInspector::class);

    expect(fn () => $action->handle($admin, $profile))
        ->toThrow(IncompleteInspectorDocumentsException::class);
});

it('ONB-T10 double action on same submission fails on second attempt with clean domain exception (ONB-E6)', function () {
    $admin1 = User::factory()->create(['role' => UserRole::Admin]);
    $admin2 = User::factory()->create(['role' => UserRole::Admin]);
    $inspectorUser = User::factory()->create(['role' => UserRole::Inspector]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);

    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Ktp]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Certificate]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Cv]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Portfolio]);

    $action = app(ApproveInspector::class);

    // First admin approves
    $action->handle($admin1, $profile);

    // Second admin tries on the now-approved profile
    expect(fn () => $action->handle($admin2, $profile))
        ->toThrow(InvalidVerificationStatusTransitionException::class);
});
