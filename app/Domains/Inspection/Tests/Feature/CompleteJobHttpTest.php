<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('JOB-T15 requester can complete result_submitted job once; second attempt idempotent', function () {
    Event::fake([JobCompleted::class]);

    $requester = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $seller = User::factory()->user()->create();

    $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::ResultSubmitted,
        'fee_amount' => 50000,
        'platform_fee_pct' => 10,
    ]);

    $payment = Payment::factory()->create([
        'payer_id' => $requester->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 50000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Qris,
    ]);

    // First completion
    $response = actingAs($requester)
        ->post(route('jobs.complete', ['job' => $job->id]));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'job_id' => (string) $job->id,
                'status' => 'completed',
            ],
            'error' => null,
        ]);

    Event::assertDispatched(JobCompleted::class, 1);

    // Second completion (idempotent)
    $response2 = actingAs($requester)
        ->post(route('jobs.complete', ['job' => $job->id]));

    $response2->assertOk()
        ->assertJson([
            'data' => [
                'job_id' => (string) $job->id,
                'status' => 'completed',
            ],
            'error' => null,
        ]);

    Event::assertDispatched(JobCompleted::class, 1); // Still 1, not 2
});

it('JOB-T18 forbids non-requester user from completing job', function () {
    $requester = User::factory()->user()->create();
    $otherUser = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();

    $listing = Listing::factory()->inInspection()->create();
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::ResultSubmitted,
    ]);

    $response = actingAs($otherUser)
        ->post(route('jobs.complete', ['job' => $job->id]));

    $response->assertForbidden();
});

it('JOB-T18 forbids inspector from completing job', function () {
    $requester = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();

    InspectorProfile::factory()->create([
        'user_id' => $inspector->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => ['car', 'motorcycle'],
    ]);

    $listing = Listing::factory()->inInspection()->create();
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::ResultSubmitted,
    ]);

    $response = actingAs($inspector)
        ->post(route('jobs.complete', ['job' => $job->id]));

    $response->assertForbidden();
});

it('JOB-T18 forbids admin from completing job via jobs.complete route', function () {
    $admin = User::factory()->admin()->create();
    $requester = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();

    $listing = Listing::factory()->inInspection()->create();
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::ResultSubmitted,
    ]);

    $response = actingAs($admin)
        ->post(route('jobs.complete', ['job' => $job->id]));

    $response->assertForbidden();
});

it('forbids completing job not in result_submitted status', function () {
    $requester = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();

    $listing = Listing::factory()->active()->create();
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Paid,
    ]);

    $response = actingAs($requester)
        ->post(route('jobs.complete', ['job' => $job->id]));

    $response->assertForbidden();
});
