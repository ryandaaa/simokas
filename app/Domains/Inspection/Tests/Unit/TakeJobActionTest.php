<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\TakeJob;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobTaken;
use App\Domains\Inspection\Exceptions\InspectorNotVerifiedException;
use App\Domains\Inspection\Exceptions\InspectorSpecialtyMismatchException;
use App\Domains\Inspection\Exceptions\JobAlreadyTakenException;
use App\Domains\Inspection\Exceptions\MaxActiveJobsException;
use App\Domains\Inspection\Exceptions\OwnListingJobException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createVerifiedInspector(array $specialties = ['car', 'motorcycle']): User
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

it('JOB-T03 lets only one inspector take a paid job; the second fails with JOB_ALREADY_TAKEN and data is unchanged', function () {
    Event::fake([JobTaken::class]);

    $firstInspector = createVerifiedInspector();
    $secondInspector = createVerifiedInspector();

    $listing = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Car,
    ]);
    $job = InspectionJob::factory()->paid()->create([
        'listing_id' => $listing->id,
        'fee_amount' => 50000,
    ]);

    $action = app(TakeJob::class);

    // First inspector successfully takes the job
    $takenJob = $action->handle($firstInspector, $job);

    expect($takenJob->status)->toBe(JobStatus::Taken)
        ->and($takenJob->inspector_id)->toBe($firstInspector->id)
        ->and($takenJob->taken_at)->not->toBeNull();

    Event::assertDispatched(JobTaken::class, 1);
    Event::assertDispatched(JobTaken::class, function (JobTaken $event) use ($job, $listing, $firstInspector) {
        return $event->jobId === (string) $job->id
            && $event->listingId === (string) $listing->id
            && $event->inspectorId === (string) $firstInspector->id;
    });

    // Second inspector tries to take the same job -> fails with 409 JobAlreadyTakenException
    expect(fn () => $action->handle($secondInspector, $job))
        ->toThrow(JobAlreadyTakenException::class);

    // Job data remains with first inspector
    $freshJob = $job->refresh();
    expect($freshJob->status)->toBe(JobStatus::Taken)
        ->and($freshJob->inspector_id)->toBe($firstInspector->id);

    // Event was not dispatched a second time
    Event::assertDispatched(JobTaken::class, 1);
});

it('JOB-T03 conditional update directly via query affects 0 rows once already taken', function () {
    $inspector = createVerifiedInspector();
    $listing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
    $job = InspectionJob::factory()->paid()->create(['listing_id' => $listing->id]);

    $action = app(TakeJob::class);
    $action->handle($inspector, $job);

    // Direct query conditional update attempt
    $affected = InspectionJob::query()
        ->whereKey($job->id)
        ->where('status', JobStatus::Paid)
        ->whereNull('inspector_id')
        ->update([
            'inspector_id' => $inspector->id,
            'status' => JobStatus::Taken,
        ]);

    expect($affected)->toBe(0);
});

it('JOB-T05 rejects taking job on dataset: unverified inspector, own listing, max 3 jobs, specialty mismatch', function (string $scenario) {
    Event::fake([JobTaken::class]);

    $action = app(TakeJob::class);

    if ($scenario === 'unverified_inspector') {
        $inspector = User::factory()->create([
            'role' => UserRole::Inspector,
            'status' => UserStatus::Pending,
        ]);
        InspectorProfile::factory()->create([
            'user_id' => $inspector->id,
            'verification_status' => VerificationStatus::Pending,
            'vehicle_types' => ['car', 'motorcycle'],
        ]);

        $listing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
        $job = InspectionJob::factory()->paid()->create(['listing_id' => $listing->id]);

        expect(fn () => $action->handle($inspector, $job))
            ->toThrow(InspectorNotVerifiedException::class);
    } elseif ($scenario === 'own_listing') {
        $inspector = createVerifiedInspector();
        // Listing is owned by the inspector themselves
        $listing = Listing::factory()->active()->create([
            'seller_id' => $inspector->id,
            'vehicle_type' => VehicleType::Car,
        ]);
        $job = InspectionJob::factory()->paid()->create(['listing_id' => $listing->id]);

        expect(fn () => $action->handle($inspector, $job))
            ->toThrow(OwnListingJobException::class);
    } elseif ($scenario === 'max_3_active_jobs') {
        $inspector = createVerifiedInspector();

        // Create 3 active jobs already assigned to this inspector
        foreach ([JobStatus::Taken, JobStatus::Scheduled, JobStatus::InProgress] as $activeStatus) {
            $l = Listing::factory()->active()->create();
            InspectionJob::factory()->create([
                'listing_id' => $l->id,
                'inspector_id' => $inspector->id,
                'status' => $activeStatus,
            ]);
        }

        $newListing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
        $newJob = InspectionJob::factory()->paid()->create(['listing_id' => $newListing->id]);

        expect(fn () => $action->handle($inspector, $newJob))
            ->toThrow(MaxActiveJobsException::class);
    } elseif ($scenario === 'specialty_mismatch') {
        // Inspector only allowed 'motorcycle'
        $inspector = createVerifiedInspector(['motorcycle']);

        // Listing is a 'car'
        $listing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
        $job = InspectionJob::factory()->paid()->create(['listing_id' => $listing->id]);

        expect(fn () => $action->handle($inspector, $job))
            ->toThrow(InspectorSpecialtyMismatchException::class);
    }

    Event::assertNotDispatched(JobTaken::class);
})->with([
    'unverified inspector' => 'unverified_inspector',
    'own listing' => 'own_listing',
    'max 3 active jobs' => 'max_3_active_jobs',
    'specialty mismatch' => 'specialty_mismatch',
]);

it('allows inspector with completed or cancelled jobs to take a new job when active jobs < 3', function () {
    $inspector = createVerifiedInspector();

    // 2 active jobs
    InspectionJob::factory()->create([
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Taken,
    ]);
    InspectionJob::factory()->create([
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Scheduled,
    ]);

    // 2 inactive/final jobs (completed and cancelled)
    InspectionJob::factory()->create([
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Completed,
    ]);
    InspectionJob::factory()->create([
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Cancelled,
    ]);

    $newListing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
    $newJob = InspectionJob::factory()->paid()->create(['listing_id' => $newListing->id]);

    $action = app(TakeJob::class);
    $result = $action->handle($inspector, $newJob);

    expect($result->status)->toBe(JobStatus::Taken)
        ->and($result->inspector_id)->toBe($inspector->id);
});
