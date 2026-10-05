<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\CancelJobByRequester;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Exceptions\CannotCancelInProgressJobException;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('JOB-T13 tests requester cancellation per status dataset according to flow doc section 3', function (JobStatus $initialStatus, bool $expectRefund) {
    Event::fake([JobCancelled::class]);

    $requester = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();

    $job = match ($initialStatus) {
        JobStatus::Requested => InspectionJob::factory()->requested()->create(['requester_id' => $requester->id]),
        JobStatus::Paid => InspectionJob::factory()->paid()->create(['requester_id' => $requester->id]),
        JobStatus::Taken => InspectionJob::factory()->taken($inspector)->create(['requester_id' => $requester->id]),
        JobStatus::Scheduled => InspectionJob::factory()->scheduled($inspector)->create(['requester_id' => $requester->id]),
        default => throw new \InvalidArgumentException("Unexpected status {$initialStatus->value}"),
    };

    $payment = null;
    if ($expectRefund) {
        $payment = Payment::factory()->create([
            'payer_id' => $requester->id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $job->id,
            'amount' => 50000,
            'status' => PaymentStatus::Success,
            'method' => PaymentMethod::Qris,
        ]);
    }

    /** @var CancelJobByRequester $action */
    $action = app(CancelJobByRequester::class);
    $result = $action->handle($requester, $job, 'Alasan pembatalan');

    expect($result->status)->toBe(JobStatus::Cancelled)
        ->and($result->cancel_reason)->toBe('Alasan pembatalan')
        ->and($result->cancelled_by)->toBe($requester->id);

    if ($expectRefund) {
        expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
            ->and(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);
    } else {
        expect(LedgerEntry::query()->count())->toBe(0);
    }

    Event::assertDispatched(JobCancelled::class, function (JobCancelled $event) use ($job, $requester) {
        return $event->jobId === $job->id
            && $event->listingId === $job->listing_id
            && $event->cancelledBy === $requester->id;
    });
})->with([
    'requested status (no refund)' => [JobStatus::Requested, false],
    'paid status (100% refund)' => [JobStatus::Paid, true],
    'taken status (100% refund)' => [JobStatus::Taken, true],
    'scheduled status (100% refund)' => [JobStatus::Scheduled, true],
]);

it('JOB-T14 & JOB-E17 rejects requester cancellation when status is in_progress', function () {
    $requester = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $job = InspectionJob::factory()->inProgress($inspector)->create(['requester_id' => $requester->id]);

    /** @var CancelJobByRequester $action */
    $action = app(CancelJobByRequester::class);

    expect(fn () => $action->handle($requester, $job, 'Ingin batal saat inspeksi'))
        ->toThrow(CannotCancelInProgressJobException::class);
});

it('JOB-E11 ensures refund is idempotent: calling cancel repeatedly does not duplicate ledger entries', function () {
    $requester = User::factory()->user()->create();
    $job = InspectionJob::factory()->paid()->create(['requester_id' => $requester->id]);

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
    $result1 = $action->handle($requester, $job, 'Batal pertama');

    expect($result1->status)->toBe(JobStatus::Cancelled);
    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);

    // Repeated call on the fresh job
    $result2 = $action->handle($requester, $job->fresh(), 'Batal kedua');
    expect($result2->status)->toBe(JobStatus::Cancelled);
    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);
});

it('rejects requester cancellation by a different user', function () {
    $owner = User::factory()->user()->create();
    $otherUser = User::factory()->user()->create();
    $job = InspectionJob::factory()->requested()->create(['requester_id' => $owner->id]);

    /** @var CancelJobByRequester $action */
    $action = app(CancelJobByRequester::class);

    expect(fn () => $action->handle($otherUser, $job))
        ->toThrow(InvalidJobStatusTransitionException::class);
});
