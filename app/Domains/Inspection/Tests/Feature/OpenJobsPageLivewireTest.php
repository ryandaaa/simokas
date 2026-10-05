<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\TakeJob;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Livewire\OpenJobsPage;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function makeApprovedInspector(array $specialties = ['car', 'motorcycle']): User
{
    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => $specialties,
    ]);

    return $user;
}

it('JOB-T18 restricts /jobs route access by role', function (string $roleType) {
    $actingUser = match ($roleType) {
        'guest' => null,
        'unverified_inspector' => tap(User::factory()->inspector()->create(['status' => 'pending']), function ($u) {
            InspectorProfile::factory()->create([
                'user_id' => $u->id,
                'verification_status' => VerificationStatus::Pending,
            ]);
        }),
        'user' => User::factory()->user()->create(),
        'admin' => User::factory()->admin()->create(),
        'approved_inspector' => makeApprovedInspector(),
        default => throw new \InvalidArgumentException("Unknown role type: {$roleType}"),
    };

    $request = $actingUser !== null ? actingAs($actingUser) : test();
    $response = get(route('jobs.index'));

    if ($roleType === 'guest') {
        $response->assertRedirect(route('login'));
    } elseif ($roleType === 'approved_inspector') {
        $response->assertOk();
    } else {
        $response->assertForbidden();
    }
})->with([
    'guest' => 'guest',
    'unverified inspector' => 'unverified_inspector',
    'regular user' => 'user',
    'admin' => 'admin',
    'approved inspector' => 'approved_inspector',
]);

it('also serves jobs.open route alias for approved inspectors', function () {
    $inspector = makeApprovedInspector();
    actingAs($inspector)->get(route('jobs.open'))->assertOk();
});

it('renders open jobs list and filters out own listings and non-matching specialties', function () {
    $inspector = makeApprovedInspector(['car']);

    // Own listing job -> must be excluded (JOB-R4, JOB-E5)
    $ownListing = Listing::factory()->active()->create([
        'seller_id' => $inspector->id,
        'vehicle_type' => VehicleType::Car,
        'brand' => 'OwnCarBrand',
    ]);
    InspectionJob::factory()->paid()->create(['listing_id' => $ownListing->id]);

    // Other listing with car -> must be shown
    $carListing = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Car,
        'brand' => 'AllowedCarBrand',
        'model' => 'ModelX',
        'year' => 2021,
        'area_name' => 'Tampan',
    ]);
    $carJob = InspectionJob::factory()->paid()->create([
        'listing_id' => $carListing->id,
        'fee_amount' => 50000,
    ]);

    // Motorcycle listing -> must be excluded for car-only inspector (ONB-R5)
    $motorListing = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Motorcycle,
        'brand' => 'ForbiddenMotorBrand',
    ]);
    InspectionJob::factory()->paid()->create(['listing_id' => $motorListing->id]);

    actingAs($inspector);

    Livewire::test(OpenJobsPage::class)
        ->assertSee('AllowedCarBrand ModelX')
        ->assertSee('2021')
        ->assertSee('Tampan')
        ->assertDontSee('OwnCarBrand')
        ->assertDontSee('ForbiddenMotorBrand');
});

it('filters open jobs by area, vehicle type, and fee range via Livewire', function () {
    $inspector = makeApprovedInspector();

    $l1 = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Car,
        'brand' => 'Toyota',
        'model' => 'Camry',
        'area_name' => 'Senapelan',
    ]);
    InspectionJob::factory()->paid()->create(['listing_id' => $l1->id, 'fee_amount' => 75000]);

    $l2 = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Motorcycle,
        'brand' => 'Honda',
        'model' => 'Vario',
        'area_name' => 'Rumbai',
    ]);
    InspectionJob::factory()->paid()->create(['listing_id' => $l2->id, 'fee_amount' => 15000]);

    actingAs($inspector);

    // Filter by area
    Livewire::test(OpenJobsPage::class)
        ->set('area', 'Senapelan')
        ->call('applyFilter')
        ->assertSee('Toyota Camry')
        ->assertDontSee('Honda Vario');

    // Filter by vehicleType
    Livewire::test(OpenJobsPage::class)
        ->set('vehicleType', 'motorcycle')
        ->call('applyFilter')
        ->assertSee('Honda Vario')
        ->assertDontSee('Toyota Camry');

    // Filter by feeMin
    Livewire::test(OpenJobsPage::class)
        ->set('feeMin', 50000)
        ->call('applyFilter')
        ->assertSee('Toyota Camry')
        ->assertDontSee('Honda Vario');
});

it('renders empty state when no open jobs match', function () {
    $inspector = makeApprovedInspector();
    actingAs($inspector);

    Livewire::test(OpenJobsPage::class)
        ->assertSee(__('inspection.open_jobs.empty_title'));
});

it('opens modal and allows inspector to take job atomically with redirect', function () {
    $inspector = makeApprovedInspector();
    $listing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
    $job = InspectionJob::factory()->paid()->create(['listing_id' => $listing->id]);

    actingAs($inspector);

    Livewire::test(OpenJobsPage::class)
        ->call('confirmTake', (string) $job->id)
        ->assertSet('confirmingJobId', (string) $job->id)
        ->call('take')
        ->assertRedirect(route('jobs.show', ['job' => $job->id]));

    expect($job->refresh()->status)->toBe(JobStatus::Taken)
        ->and($job->inspector_id)->toBe($inspector->id);
});

it('JOB-E1 handles conflict 409 gracefully in Livewire UI when job already taken', function () {
    $inspector = makeApprovedInspector();
    $otherInspector = makeApprovedInspector();

    $listing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
    $job = InspectionJob::factory()->paid()->create(['listing_id' => $listing->id]);

    actingAs($inspector);

    $component = Livewire::test(OpenJobsPage::class)
        ->call('confirmTake', (string) $job->id);

    // Other inspector takes job first in the background
    app(TakeJob::class)->handle($otherInspector, $job);

    // First inspector presses confirm take -> conflict handled with message and refreshed list
    $component->call('take')
        ->assertSet('confirmingJobId', null)
        ->assertSee(__('inspection.errors.job_already_taken'));
});

it('handles max 3 active jobs gracefully in Livewire UI', function () {
    $inspector = makeApprovedInspector();

    // Give inspector 3 active jobs already
    foreach ([JobStatus::Taken, JobStatus::Scheduled, JobStatus::InProgress] as $st) {
        $l = Listing::factory()->active()->create();
        InspectionJob::factory()->create([
            'listing_id' => $l->id,
            'inspector_id' => $inspector->id,
            'status' => $st,
        ]);
    }

    $newListing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
    $newJob = InspectionJob::factory()->paid()->create(['listing_id' => $newListing->id]);

    actingAs($inspector);

    Livewire::test(OpenJobsPage::class)
        ->call('confirmTake', (string) $newJob->id)
        ->call('take')
        ->assertSet('confirmingJobId', null)
        ->assertSee(__('inspection.errors.max_active_jobs'));
});

it('avoids N+1 query problem when loading open jobs', function () {
    $inspector = makeApprovedInspector();

    // Create 1 job
    $l1 = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
    InspectionJob::factory()->paid()->create(['listing_id' => $l1->id]);

    actingAs($inspector);

    // Warm up
    Livewire::test(OpenJobsPage::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::test(OpenJobsPage::class);
    $queryCountSingle = count(DB::getQueryLog());

    // Create 5 more jobs
    for ($i = 0; $i < 5; $i++) {
        $l = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
        InspectionJob::factory()->paid()->create(['listing_id' => $l->id]);
    }

    DB::flushQueryLog();

    Livewire::test(OpenJobsPage::class);
    $queryCountMultiple = count(DB::getQueryLog());

    // Query count should remain constant (no N+1 per job)
    expect($queryCountMultiple)->toBeLessThanOrEqual($queryCountSingle + 1);
});
