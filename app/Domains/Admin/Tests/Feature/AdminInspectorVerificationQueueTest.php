<?php

declare(strict_types=1);

namespace App\Domains\Admin\Tests\Feature;

use App\Domains\Admin\Livewire\InspectorVerificationQueue;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('ADM-T01 admin inspector verification route is protected and only accessible by admin (dataset role)', function (UserRole $role, int $expectedStatus) {
    $user = User::factory()->create(['role' => $role]);
    actingAs($user);

    $response = get('/admin/inspectors');

    $response->assertStatus($expectedStatus);
})->with([
    'admin has access' => [UserRole::Admin, 200],
    'user is forbidden' => [UserRole::User, 403],
    'inspector is forbidden' => [UserRole::Inspector, 403],
]);

it('unauthenticated user is redirected to login from admin inspector queue', function () {
    $response = get('/admin/inspectors');

    $response->assertRedirect(route('login'));
});

it('renders empty state when there are no pending inspectors with complete documents', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    Livewire::actingAs($admin)
        ->test(InspectorVerificationQueue::class)
        ->assertSee(__('admin.inspector_queue.empty_title'));
});

it('only displays pending inspectors with complete documents in the queue (ONB-E7)', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    // Complete inspector
    $completeUser = User::factory()->create(['name' => 'Budi Lengkap', 'role' => UserRole::Inspector]);
    $completeProfile = InspectorProfile::factory()->create([
        'user_id' => $completeUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);
    InspectorDocument::factory()->create(['user_id' => $completeUser->id, 'type' => DocumentType::Ktp]);
    InspectorDocument::factory()->create(['user_id' => $completeUser->id, 'type' => DocumentType::Certificate]);
    InspectorDocument::factory()->create(['user_id' => $completeUser->id, 'type' => DocumentType::Cv]);
    InspectorDocument::factory()->create(['user_id' => $completeUser->id, 'type' => DocumentType::Portfolio]);

    // Incomplete inspector
    $incompleteUser = User::factory()->create(['name' => 'Andi Tidak Lengkap', 'role' => UserRole::Inspector]);
    $incompleteProfile = InspectorProfile::factory()->create([
        'user_id' => $incompleteUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);
    InspectorDocument::factory()->create(['user_id' => $incompleteUser->id, 'type' => DocumentType::Ktp]);

    Livewire::actingAs($admin)
        ->test(InspectorVerificationQueue::class)
        ->assertSee('Budi Lengkap')
        ->assertDontSee('Andi Tidak Lengkap');
});

it('ADM-T03 admin approves inspector from verification queue successfully', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $inspectorUser = User::factory()->create(['name' => 'Calon Inspektor 1', 'role' => UserRole::Inspector]);
    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Ktp]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Certificate]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Cv]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Portfolio]);

    Livewire::actingAs($admin)
        ->test(InspectorVerificationQueue::class)
        ->assertSee('Calon Inspektor 1')
        ->call('confirmApprove', $profile->id)
        ->assertSet('confirmingApproveId', $profile->id)
        ->call('approve')
        ->assertSet('confirmingApproveId', null)
        ->assertSee(__('admin.inspector_queue.approved_success'));

    $fresh = $profile->fresh();
    expect($fresh->verification_status)->toBe(VerificationStatus::Approved)
        ->and($fresh->verified_by)->toBe($admin->id);
});

it('ADM-T03 admin rejects inspector with mandatory reason from verification queue', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $inspectorUser = User::factory()->create(['name' => 'Calon Inspektor 2', 'role' => UserRole::Inspector]);
    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Ktp]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Certificate]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Cv]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Portfolio]);

    // Rejection without reason fails validation
    Livewire::actingAs($admin)
        ->test(InspectorVerificationQueue::class)
        ->call('confirmReject', $profile->id)
        ->set('rejectionReason', '')
        ->call('reject')
        ->assertHasErrors(['rejectionReason' => 'required']);

    // Rejection with reason succeeds
    $reason = 'Foto KTP tidak jelas, silakan unggah ulang berkas asli.';
    Livewire::actingAs($admin)
        ->test(InspectorVerificationQueue::class)
        ->call('confirmReject', $profile->id)
        ->set('rejectionReason', $reason)
        ->call('reject')
        ->assertHasNoErrors()
        ->assertSet('confirmingRejectId', null)
        ->assertSee(__('admin.inspector_queue.rejected_success'));

    $fresh = $profile->fresh();
    expect($fresh->verification_status)->toBe(VerificationStatus::Rejected)
        ->and($fresh->rejection_reason)->toBe($reason);
});

it('ADM-T04 double action or concurrent handling on already verified inspector shows clean error without double effect (ADM-E3)', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $inspectorUser = User::factory()->create(['name' => 'Calon Inspektor 3', 'role' => UserRole::Inspector]);
    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Ktp]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Certificate]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Cv]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Portfolio]);

    // Simulate another admin approved the profile right before our approve call
    $profile->update(['verification_status' => VerificationStatus::Approved, 'verified_by' => $admin->id]);

    Livewire::actingAs($admin)
        ->test(InspectorVerificationQueue::class)
        ->call('confirmApprove', $profile->id)
        ->call('approve')
        ->assertSet('confirmingApproveId', null)
        ->assertSet('errorMessage', __('inspector.errors.already_processed', ['status' => 'approved']));
});

it('opens and closes inspector detail view with signed document links', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $inspectorUser = User::factory()->create(['name' => 'Calon Inspektor Detail', 'role' => UserRole::Inspector]);
    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Pending,
    ]);
    InspectorDocument::factory()->create([
        'user_id' => $inspectorUser->id,
        'type' => DocumentType::Ktp,
        'storage_path' => 'inspector-documents/'.$inspectorUser->id.'/ktp.jpg',
    ]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Certificate]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Cv]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Portfolio]);

    Livewire::actingAs($admin)
        ->test(InspectorVerificationQueue::class)
        ->call('openDetail', $profile->id)
        ->assertSet('selectedProfileId', $profile->id)
        ->assertSee(__('admin.inspector_queue.detail_title', ['name' => 'Calon Inspektor Detail']))
        ->assertSee(__('inspector.documents.types.ktp'))
        ->call('closeDetail')
        ->assertSet('selectedProfileId', null);
});
