<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobPaid;
use App\Domains\Inspection\Listeners\MarkJobPaidOnPaymentSucceeded;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('ignores PaymentSucceeded events for purposes other than Inspection', function () {
    Event::fake([JobPaid::class]);

    $job = InspectionJob::factory()->requested()->create();

    $event = new PaymentSucceeded(
        paymentId: 'pay-123',
        purpose: PaymentPurpose::Purchase,
        refId: $job->id,
        amount: 50000,
        payerId: $job->requester_id,
    );

    $listener = app(MarkJobPaidOnPaymentSucceeded::class);
    $listener->handle($event);

    expect($job->fresh()->status)->toBe(JobStatus::Requested);
    Event::assertNotDispatched(JobPaid::class);
});

it('marks requested job as paid, records available_since, and dispatches JobPaid', function () {
    Event::fake([JobPaid::class]);

    $job = InspectionJob::factory()->requested()->create();

    $event = new PaymentSucceeded(
        paymentId: 'pay-123',
        purpose: PaymentPurpose::Inspection,
        refId: $job->id,
        amount: 50000,
        payerId: $job->requester_id,
    );

    $listener = app(MarkJobPaidOnPaymentSucceeded::class);
    $listener->handle($event);

    $freshJob = $job->fresh();
    expect($freshJob->status)->toBe(JobStatus::Paid)
        ->and($freshJob->available_since)->not->toBeNull();

    Event::assertDispatched(JobPaid::class, function (JobPaid $dispatched) use ($job) {
        return $dispatched->jobId === $job->id
            && $dispatched->listingId === $job->listing_id
            && $dispatched->requesterId === $job->requester_id;
    });
});

it('is idempotent: duplicate PaymentSucceeded event does not dispatch second JobPaid event and does not refund active job', function () {
    Event::fake([JobPaid::class]);

    $job = InspectionJob::factory()->requested()->create();

    $payment = Payment::factory()->create([
        'payer_id' => $job->requester_id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 50000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Qris,
    ]);

    $event = new PaymentSucceeded(
        paymentId: $payment->id,
        purpose: PaymentPurpose::Inspection,
        refId: $job->id,
        amount: 50000,
        payerId: $job->requester_id,
    );

    $listener = app(MarkJobPaidOnPaymentSucceeded::class);
    $listener->handle($event);
    $firstAvailableSince = $job->fresh()->available_since;

    // Second call with same event
    $listener->handle($event);

    expect($job->fresh()->status)->toBe(JobStatus::Paid)
        ->and($job->fresh()->available_since->toDateTimeString())->toBe($firstAvailableSince->toDateTimeString())
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Success);

    // Only one JobPaid event was dispatched
    Event::assertDispatchedTimes(JobPaid::class, 1);
});

it('automatically refunds payment when PaymentSucceeded arrives for a cancelled job without changing job', function () {
    Event::fake([JobPaid::class]);

    $job = InspectionJob::factory()->create([
        'status' => JobStatus::Cancelled,
        'cancel_reason' => 'Pemohon membatalkan',
    ]);

    $payment = Payment::factory()->create([
        'payer_id' => $job->requester_id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 50000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Qris,
    ]);

    $event = new PaymentSucceeded(
        paymentId: $payment->id,
        purpose: PaymentPurpose::Inspection,
        refId: $job->id,
        amount: 50000,
        payerId: $job->requester_id,
    );

    $listener = app(MarkJobPaidOnPaymentSucceeded::class);
    $listener->handle($event);

    // Job stays cancelled
    expect($job->fresh()->status)->toBe(JobStatus::Cancelled);
    Event::assertNotDispatched(JobPaid::class);

    // Payment is refunded via Ledger
    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);

    // Duplicate event delivery is idempotent: does not duplicate refund entry
    $listener->handle($event);
    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);
});

it('automatically refunds payment when PaymentSucceeded arrives for an expired job', function () {
    Event::fake([JobPaid::class]);

    $job = InspectionJob::factory()->create([
        'status' => JobStatus::Expired,
    ]);

    $payment = Payment::factory()->create([
        'payer_id' => $job->requester_id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 50000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Transfer,
    ]);

    $event = new PaymentSucceeded(
        paymentId: $payment->id,
        purpose: PaymentPurpose::Inspection,
        refId: $job->id,
        amount: 50000,
        payerId: $job->requester_id,
    );

    $listener = app(MarkJobPaidOnPaymentSucceeded::class);
    $listener->handle($event);

    expect($job->fresh()->status)->toBe(JobStatus::Expired)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
    Event::assertNotDispatched(JobPaid::class);
});
