<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\CancelJobByRequester;
use App\Domains\Inspection\Actions\TakeJob;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Exceptions\JobAlreadyTakenException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('JOB-E8 requester cancel wins over take-job: inspector receives 409 and no double status occurs', function () {
    $requester = User::factory()->user()->create();
    $inspectorUser = User::factory()->inspector()->create();
    InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => ['car'],
    ]);

    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'vehicle_type' => VehicleType::Car,
    ]);

    $job = InspectionJob::factory()->paid()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
    ]);

    Payment::factory()->create([
        'payer_id' => $requester->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 50000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Qris,
    ]);

    // 1. Requester cancels first
    /** @var CancelJobByRequester $cancelAction */
    $cancelAction = app(CancelJobByRequester::class);
    $cancelAction->handle($requester, $job);

    expect($job->fresh()->status)->toBe(JobStatus::Cancelled);

    // 2. Inspector tries to take the now cancelled job -> fails with 409
    /** @var TakeJob $takeAction */
    $takeAction = app(TakeJob::class);

    $thrown = false;
    try {
        $takeAction->handle($inspectorUser, $job);
    } catch (JobAlreadyTakenException $e) {
        $thrown = true;
        expect($e->getStatusCode())->toBe(409);
    }
    expect($thrown)->toBeTrue();

    // Status remains cancelled, inspector_id remains null
    expect($job->fresh()->status)->toBe(JobStatus::Cancelled)
        ->and($job->fresh()->inspector_id)->toBeNull();
});

it('JOB-E8 inspector take-job wins over requester cancel: requester receives 409 conflict and no double status occurs', function () {
    $requester = User::factory()->user()->create();
    $inspectorUser = User::factory()->inspector()->create();
    InspectorProfile::factory()->create([
        'user_id' => $inspectorUser->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => ['car'],
    ]);

    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'vehicle_type' => VehicleType::Car,
    ]);

    $job = InspectionJob::factory()->paid()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
    ]);

    Payment::factory()->create([
        'payer_id' => $requester->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 50000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Qris,
    ]);

    // Snapshot job instance as 'paid' before race
    $requesterJobView = clone $job;

    // 1. Inspector takes job first
    /** @var TakeJob $takeAction */
    $takeAction = app(TakeJob::class);
    $takeAction->handle($inspectorUser, $job);

    expect($job->fresh()->status)->toBe(JobStatus::Taken)
        ->and($job->fresh()->inspector_id)->toBe($inspectorUser->id);

    // 2. Requester tries to cancel based on the old 'paid' view
    /** @var CancelJobByRequester $cancelAction */
    $cancelAction = app(CancelJobByRequester::class);

    $thrown = false;
    try {
        $cancelAction->handle($requester, $requesterJobView);
    } catch (InvalidJobStatusTransitionException $e) {
        $thrown = true;
        expect($e->getStatusCode())->toBe(409);
    }
    expect($thrown)->toBeTrue();

    // Status remains taken by the inspector
    expect($job->fresh()->status)->toBe(JobStatus::Taken)
        ->and($job->fresh()->inspector_id)->toBe($inspectorUser->id);
});
