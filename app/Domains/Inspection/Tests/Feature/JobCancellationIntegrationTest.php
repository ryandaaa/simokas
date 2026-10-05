<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\CancelJobByAdmin;
use App\Domains\Inspection\Actions\CancelJobByInspector;
use App\Domains\Inspection\Actions\CancelJobByRequester;
use App\Domains\Inspection\Actions\ScheduleJob;
use App\Domains\Inspection\Actions\StartInspection;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Exceptions\CannotCancelInProgressJobException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('executes full integration: requester cancels paid job -> refund processed -> listing returns to active via real event', function () {
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->create();

    $job = InspectionJob::factory()->paid()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
    ]);

    $payment = Payment::factory()->create([
        'payer_id' => $requester->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 50000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Qris,
    ]);

    /** @var CancelJobByRequester $action */
    $action = app(CancelJobByRequester::class);
    $action->handle($requester, $job, 'Pembatalan sebelum inspeksi');

    // 1. Job is cancelled
    expect($job->fresh()->status)->toBe(JobStatus::Cancelled);

    // 2. Payment is refunded and ledger entry recorded
    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);

    // 3. Real Listing listener reacted to JobCancelled event: listing returned to active
    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('executes full integration: late payment on cancelled job is automatically refunded and job remains cancelled (Risk #2)', function () {
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create();

    // Job was created and cancelled while still requested
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'status' => JobStatus::Cancelled,
        'cancel_reason' => 'Batal sebelum bayar',
    ]);

    $payment = Payment::factory()->create([
        'payer_id' => $requester->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 50000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Transfer,
    ]);

    // Late payment event arrives for the already cancelled job
    PaymentSucceeded::dispatch(
        paymentId: $payment->id,
        purpose: PaymentPurpose::Inspection,
        refId: $job->id,
        amount: 50000,
        payerId: $requester->id,
    );

    // Job status did not change
    expect($job->fresh()->status)->toBe(JobStatus::Cancelled);

    // Payment was refunded via Ledger automatically
    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);

    // Duplicate delivery of the event is idempotent (PAY-E7, JOB-E11)
    PaymentSucceeded::dispatch(
        paymentId: $payment->id,
        purpose: PaymentPurpose::Inspection,
        refId: $job->id,
        amount: 50000,
        payerId: $requester->id,
    );

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);
});

it('executes full lifecycle: schedule -> start -> requester blocked from cancelling -> admin cancels and listing returns to active', function () {
    $admin = User::factory()->admin()->create();
    $inspector = User::factory()->inspector()->create();
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->create();

    $job = InspectionJob::factory()->taken($inspector)->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
    ]);

    $payment = Payment::factory()->create([
        'payer_id' => $requester->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 50000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Qris,
    ]);

    // 1. Inspector schedules job
    /** @var ScheduleJob $scheduleAction */
    $scheduleAction = app(ScheduleJob::class);
    $scheduleAction->handle($inspector, $job, now()->addDays(2));
    expect($job->fresh()->status)->toBe(JobStatus::Scheduled);

    // 2. Inspector starts inspection
    /** @var StartInspection $startAction */
    $startAction = app(StartInspection::class);
    $startAction->handle($inspector, $job->fresh());
    expect($job->fresh()->status)->toBe(JobStatus::InProgress);

    // 3. Requester attempts to cancel -> rejected (JOB-E17, JOB-T14)
    /** @var CancelJobByRequester $requesterCancelAction */
    $requesterCancelAction = app(CancelJobByRequester::class);
    expect(fn () => $requesterCancelAction->handle($requester, $job->fresh()))
        ->toThrow(CannotCancelInProgressJobException::class);

    // 4. Admin cancels the in-progress job with refund
    /** @var CancelJobByAdmin $adminCancelAction */
    $adminCancelAction = app(CancelJobByAdmin::class);
    $adminCancelAction->handle($admin, $job->fresh(), 'Admin intervensi', refund: true);

    expect($job->fresh()->status)->toBe(JobStatus::Cancelled)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1)
        ->and($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('inspector cancels taken job: returns to paid and listing stays in_inspection', function () {
    $inspector = User::factory()->inspector()->create();
    $listing = Listing::factory()->inInspection()->create();

    $job = InspectionJob::factory()->taken($inspector)->create([
        'listing_id' => $listing->id,
        'available_since' => now()->subDays(4),
    ]);

    /** @var CancelJobByInspector $action */
    $action = app(CancelJobByInspector::class);
    $action->handle($inspector, $job, 'Penjual berhalangan');

    $fresh = $job->fresh();
    expect($fresh->status)->toBe(JobStatus::Paid)
        ->and($fresh->inspector_id)->toBeNull()
        ->and($fresh->scheduled_at)->toBeNull()
        ->and($fresh->available_since->isToday())->toBeTrue()
        ->and($listing->fresh()->status)->toBe(ListingStatus::InInspection);
});
