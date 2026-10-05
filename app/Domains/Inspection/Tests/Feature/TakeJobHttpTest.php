<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobTaken;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

function makeVerifiedInspector(array $specialties = ['car', 'motorcycle']): User
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

it('JOB-T03 lets only one inspector take a paid job via HTTP; the second receives 409 JOB_ALREADY_TAKEN', function () {
    Event::fake([JobTaken::class]);

    $firstInspector = makeVerifiedInspector();
    $secondInspector = makeVerifiedInspector();

    $listing = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Car,
        'lat' => '0.5071000',
        'lng' => '101.4478000',
        'address_full' => 'Jl. Tuanku Tambusai No. 12',
        'chassis_number' => 'MHKA1234567890123',
    ]);
    $job = InspectionJob::factory()->paid()->create([
        'listing_id' => $listing->id,
        'fee_amount' => 50000,
    ]);

    // First inspector takes job
    $response1 = $this->actingAs($firstInspector)
        ->post(route('jobs.take', ['job' => $job->id]));

    $response1->assertOk()
        ->assertJson([
            'data' => [
                'job_id' => (string) $job->id,
                'status' => 'taken',
                'location' => [
                    'lat' => 0.5071,
                    'lng' => 101.4478,
                    'address_full' => 'Jl. Tuanku Tambusai No. 12',
                ],
                'chassis_number' => 'MHKA1234567890123',
            ],
            'error' => null,
        ]);

    Event::assertDispatched(JobTaken::class, 1);

    // Second inspector attempts to take same job -> 409 Conflict
    $response2 = $this->actingAs($secondInspector)
        ->post(route('jobs.take', ['job' => $job->id]));

    $response2->assertStatus(409)
        ->assertJson([
            'data' => null,
            'error' => [
                'code' => 'JOB_ALREADY_TAKEN',
            ],
        ]);

    // Database state remains assigned to first inspector
    expect($job->refresh()->inspector_id)->toBe($firstInspector->id)
        ->and($job->status)->toBe(JobStatus::Taken);

    // Event not dispatched again
    Event::assertDispatched(JobTaken::class, 1);
});

it('JOB-T05 rejects taking job via HTTP on dataset: unverified, own listing, max 3 jobs, specialty mismatch', function (string $scenario) {
    Event::fake([JobTaken::class]);

    if ($scenario === 'unverified') {
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

        $response = $this->actingAs($inspector)->post(route('jobs.take', ['job' => $job->id]));
        $response->assertForbidden();
    } elseif ($scenario === 'own_listing') {
        $inspector = makeVerifiedInspector();
        $listing = Listing::factory()->active()->create([
            'seller_id' => $inspector->id,
            'vehicle_type' => VehicleType::Car,
        ]);
        $job = InspectionJob::factory()->paid()->create(['listing_id' => $listing->id]);

        $response = $this->actingAs($inspector)->post(route('jobs.take', ['job' => $job->id]));
        $response->assertForbidden()
            ->assertJson([
                'data' => null,
                'error' => [
                    'code' => 'OWN_LISTING',
                ],
            ]);
    } elseif ($scenario === 'max_3_active_jobs') {
        $inspector = makeVerifiedInspector();
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

        $response = $this->actingAs($inspector)->post(route('jobs.take', ['job' => $newJob->id]));
        $response->assertStatus(409)
            ->assertJson([
                'data' => null,
                'error' => [
                    'code' => 'MAX_ACTIVE_JOBS',
                ],
            ]);
    } elseif ($scenario === 'specialty_mismatch') {
        $inspector = makeVerifiedInspector(['motorcycle']);
        $carListing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
        $job = InspectionJob::factory()->paid()->create(['listing_id' => $carListing->id]);

        $response = $this->actingAs($inspector)->post(route('jobs.take', ['job' => $job->id]));
        $response->assertForbidden();
    }

    Event::assertNotDispatched(JobTaken::class);
})->with([
    'unverified inspector' => 'unverified',
    'own listing' => 'own_listing',
    'max 3 active jobs' => 'max_3_active_jobs',
    'specialty mismatch' => 'specialty_mismatch',
]);

it('JOB-T18 forbids unauthorized roles from POST jobs.take', function (string $roleType) {
    $listing = Listing::factory()->active()->create(['vehicle_type' => VehicleType::Car]);
    $job = InspectionJob::factory()->paid()->create(['listing_id' => $listing->id]);

    $actingUser = match ($roleType) {
        'guest' => null,
        'user' => User::factory()->user()->create(),
        'admin' => User::factory()->admin()->create(),
        'blocked_inspector' => tap(makeVerifiedInspector(), fn ($u) => $u->update(['status' => 'blocked'])),
        default => throw new \InvalidArgumentException("Unknown role type: {$roleType}"),
    };

    $request = $actingUser !== null
        ? $this->actingAs($actingUser)
        : $this;

    $response = $request->post(route('jobs.take', ['job' => $job->id]));

    if ($roleType === 'guest') {
        $response->assertRedirect(route('login'));
    } elseif ($roleType === 'blocked_inspector') {
        $response->assertRedirect(route('identity.blocked'));
    } else {
        $response->assertForbidden();
    }
})->with([
    'guest' => 'guest',
    'regular user' => 'user',
    'admin' => 'admin',
    'blocked inspector' => 'blocked_inspector',
]);
