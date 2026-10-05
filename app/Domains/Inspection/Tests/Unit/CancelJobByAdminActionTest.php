<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\CancelJobByAdmin;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('JOB-T14 allows admin to cancel in_progress job', function (bool $refund) {
    Event::fake([JobCancelled::class]);

    $admin = User::factory()->admin()->create();
    $inspector = User::factory()->inspector()->create();
    $requester = User::factory()->user()->create();

    $job = InspectionJob::factory()->inProgress($inspector)->create([
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

    /** @var CancelJobByAdmin $action */
    $action = app(CancelJobByAdmin::class);
    $result = $action->handle($admin, $job, 'Dibatalkan oleh admin atas kendala teknis', $refund);

    expect($result->status)->toBe(JobStatus::Cancelled)
        ->and($result->cancelled_by)->toBe($admin->id);

    if ($refund) {
        expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
            ->and(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);
    } else {
        expect($payment->fresh()->status)->toBe(PaymentStatus::Success)
            ->and(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
    }

    Event::assertDispatched(JobCancelled::class);
})->with([
    'without refund' => [false],
    'with refund' => [true],
]);

it('allows admin to cancel paid, taken, and scheduled jobs with 100% refund', function (JobStatus $status) {
    Event::fake([JobCancelled::class]);

    $admin = User::factory()->admin()->create();
    $inspector = User::factory()->inspector()->create();
    $requester = User::factory()->user()->create();

    $job = match ($status) {
        JobStatus::Paid => InspectionJob::factory()->paid()->create(['requester_id' => $requester->id]),
        JobStatus::Taken => InspectionJob::factory()->taken($inspector)->create(['requester_id' => $requester->id]),
        JobStatus::Scheduled => InspectionJob::factory()->scheduled($inspector)->create(['requester_id' => $requester->id]),
        default => throw new \InvalidArgumentException,
    };

    $payment = Payment::factory()->create([
        'payer_id' => $requester->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 50000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Transfer,
    ]);

    /** @var CancelJobByAdmin $action */
    $action = app(CancelJobByAdmin::class);
    $result = $action->handle($admin, $job, 'Admin cancel');

    expect($result->status)->toBe(JobStatus::Cancelled)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);
})->with([
    JobStatus::Paid,
    JobStatus::Taken,
    JobStatus::Scheduled,
]);

it('rejects cancellation by non-admin user', function () {
    $regularUser = User::factory()->user()->create();
    $job = InspectionJob::factory()->requested()->create();

    /** @var CancelJobByAdmin $action */
    $action = app(CancelJobByAdmin::class);

    expect(fn () => $action->handle($regularUser, $job))
        ->toThrow(InvalidJobStatusTransitionException::class);
});
