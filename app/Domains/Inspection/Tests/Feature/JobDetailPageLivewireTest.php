<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Livewire\JobDetailPage;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function createDetailInspector(): User
{
    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => ['car', 'motorcycle'],
    ]);

    return $user;
}

it('JOB-T06 renders exact address and chassis number for assigned inspector', function () {
    $inspector = createDetailInspector();

    $listing = Listing::factory()->active()->create([
        'brand' => 'Honda',
        'model' => 'CR-V',
        'year' => 2023,
        'vehicle_type' => VehicleType::Car,
        'address_full' => 'Jl. Sudirman No. 100 Gedung B',
        'chassis_number' => 'NOHONDA123456789',
        'inspection_place' => 'seller_home',
        'lat' => '0.5199999',
        'lng' => '101.4444444',
    ]);

    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Taken,
        'fee_amount' => 50000,
        'taken_at' => now(),
    ]);

    actingAs($inspector);

    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->assertSee('Honda CR-V')
        ->assertSee('Jl. Sudirman No. 100 Gedung B')
        ->assertSee('NOHONDA123456789')
        ->assertSee('seller_home')
        ->assertSee('0.5199999, 101.4444444');
});

it('JOB-T06 allows admin to view job detail', function () {
    $admin = User::factory()->admin()->create();
    $inspector = createDetailInspector();

    $listing = Listing::factory()->active()->create([
        'address_full' => 'Jl. Admin Access No. 1',
        'chassis_number' => 'CHASSIS-ADMIN-VIEW',
    ]);

    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Taken,
    ]);

    actingAs($admin);

    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->assertOk()
        ->assertSee('Jl. Admin Access No. 1')
        ->assertSee('CHASSIS-ADMIN-VIEW');
});

it('JOB-T06 throws forbidden 403 when unassigned inspector accesses detail', function () {
    $assignedInspector = createDetailInspector();
    $otherInspector = createDetailInspector();

    $listing = Listing::factory()->active()->create();
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $assignedInspector->id,
        'status' => JobStatus::Taken,
    ]);

    actingAs($otherInspector);

    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->assertForbidden();
});
