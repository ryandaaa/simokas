<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Livewire\InspectorOnboarding;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders rejection alert and resubmit button on onboarding screen for rejected inspector', function () {
    $inspectorUser = User::factory()->create(['role' => UserRole::Inspector]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Rejected,
        'rejection_reason' => 'Foto KTP terpotong.',
    ]);

    Livewire::actingAs($inspectorUser)
        ->test(InspectorOnboarding::class)
        ->assertSee('Pengajuan Verifikasi Ditolak')
        ->assertSee('Foto KTP terpotong.')
        ->assertSee('Ajukan Ulang Verifikasi');
});

it('resubmits rejected profile successfully via Livewire onboarding component', function () {
    $inspectorUser = User::factory()->create(['role' => UserRole::Inspector]);

    $profile = InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Rejected,
        'rejection_reason' => 'KTP buram.',
    ]);

    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Ktp]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Certificate]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Cv]);
    InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Portfolio]);

    Livewire::actingAs($inspectorUser)
        ->test(InspectorOnboarding::class)
        ->call('resubmit')
        ->assertSee(__('inspector.onboarding.resubmitted_success'));

    $fresh = $profile->fresh();
    expect($fresh->verification_status)->toBe(VerificationStatus::Pending)
        ->and($fresh->rejection_reason)->toBeNull()
        ->and(count($fresh->rejection_history))->toBe(1);
});
