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

it('schedules job via form inputs and validates fields', function () {
    $inspector = createDetailInspector();
    $listing = Listing::factory()->active()->create();
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Taken,
    ]);

    actingAs($inspector);

    // 1. Missing fields validation
    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->set('scheduled_date', '')
        ->set('scheduled_time', '')
        ->call('saveSchedule')
        ->assertHasErrors(['scheduled_date', 'scheduled_time']);

    // 2. Past date validation
    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->set('scheduled_date', now()->subDays(2)->format('Y-m-d'))
        ->set('scheduled_time', '10:00')
        ->call('saveSchedule')
        ->assertHasErrors(['scheduled_time']);

    // 3. Valid future schedule
    $futureDate = now()->addDays(2)->format('Y-m-d');
    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->set('scheduled_date', $futureDate)
        ->set('scheduled_time', '14:30')
        ->call('saveSchedule')
        ->assertHasNoErrors()
        ->assertSee(__('inspection.detail.schedule_success'));

    expect($job->fresh()->status)->toBe(JobStatus::Scheduled)
        ->and($job->fresh()->scheduled_at)->not->toBeNull();
});

it('allows starting inspection once scheduled', function () {
    $inspector = createDetailInspector();
    $listing = Listing::factory()->active()->create();
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Scheduled,
        'scheduled_at' => now()->addDay(),
    ]);

    actingAs($inspector);

    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->assertSee(__('inspection.detail.start_button'))
        ->call('startInspection')
        ->assertSee(__('inspection.detail.start_success'));

    expect($job->fresh()->status)->toBe(JobStatus::InProgress)
        ->and($job->fresh()->started_at)->not->toBeNull();
});

it('allows inspector to cancel job with required reason, returning job to open list', function () {
    $inspector = createDetailInspector();
    $listing = Listing::factory()->active()->create();
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Taken,
    ]);

    actingAs($inspector);

    // Empty reason validation
    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->set('cancel_reason', '')
        ->call('cancelJob')
        ->assertHasErrors(['cancel_reason']);

    // Valid cancellation
    Livewire::test(JobDetailPage::class, ['job' => $job])
        ->set('cancel_reason', 'Penjual tidak dapat dihubungi selama 48 jam')
        ->call('cancelJob')
        ->assertHasNoErrors()
        ->assertRedirect(route('jobs.inspector'));

    $freshJob = $job->fresh();
    expect($freshJob->status)->toBe(JobStatus::Paid)
        ->and($freshJob->inspector_id)->toBeNull()
        ->and($freshJob->cancel_reason)->toBe('Penjual tidak dapat dihubungi selama 48 jam');
});
