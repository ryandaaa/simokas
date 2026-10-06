<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\CancelJobByAdmin;
use App\Domains\Inspection\Actions\CompleteJob;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Support\Clock;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ADM-T07: Admin complete result_submitted job', function () {
    it('ADM-T07 admin completes result_submitted job once, fee posts once (idempoten ADM-E4)', function () {
        Event::fake([JobCompleted::class]);

        $t0 = Carbon::parse('2026-10-06 12:00:00', Clock::TIMEZONE);
        Carbon::setTestNow($t0);

        $admin = User::factory()->admin()->create();
        $requester = User::factory()->user()->create();
        $inspector = User::factory()->inspector()->create();
        $seller = User::factory()->user()->create();

        $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

        $job = InspectionJob::factory()->create([
            'listing_id' => $listing->id,
            'requester_id' => $requester->id,
            'inspector_id' => $inspector->id,
            'status' => JobStatus::ResultSubmitted,
            'fee_amount' => 100000,
            'platform_fee_pct' => 15,
            'submitted_at' => $t0->copy()->subDays(2),
        ]);

        $payment = Payment::factory()->create([
            'payer_id' => $requester->id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $job->id,
            'amount' => 100000,
            'status' => PaymentStatus::Success,
            'method' => PaymentMethod::Qris,
        ]);

        $ledgerMock = Mockery::mock(Ledger::class);
        $ledgerMock->shouldReceive('postInspectionPayout')
            ->once()
            ->with($payment->id, $inspector->id, 15);

        app()->instance(Ledger::class, $ledgerMock);

        /** @var CompleteJob $action */
        $action = app(CompleteJob::class);

        $completedJob = $action->handle($job);

        expect($completedJob->status)->toBe(JobStatus::Completed)
            ->and($completedJob->completed_at->toIso8601String())->toBe($t0->toIso8601String());

        Event::assertDispatched(JobCompleted::class, 1);

        // Idempotent: complete again should not post again
        $again = $action->handle($completedJob);
        expect($again->status)->toBe(JobStatus::Completed);

        Event::assertDispatched(JobCompleted::class, 1); // Still 1
    });
});

describe('ADM-T08: Admin cancel job with refund options', function () {
    it('ADM-T08 admin cancels paid job with 100% refund', function () {
        Event::fake([JobCancelled::class]);

        $admin = User::factory()->admin()->create();
        $requester = User::factory()->user()->create();
        $seller = User::factory()->user()->create();

        $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

        $job = InspectionJob::factory()->create([
            'listing_id' => $listing->id,
            'requester_id' => $requester->id,
            'status' => JobStatus::Paid,
        ]);

        $payment = Payment::factory()->create([
            'payer_id' => $requester->id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $job->id,
            'amount' => 50000,
            'status' => PaymentStatus::Success,
        ]);

        $ledgerMock = Mockery::mock(Ledger::class);
        $ledgerMock->shouldReceive('refundPayment')
            ->once()
            ->with($payment->id);

        app()->instance(Ledger::class, $ledgerMock);

        /** @var CancelJobByAdmin $action */
        $action = app(CancelJobByAdmin::class);

        $cancelledJob = $action->handle($admin, $job, 'Payment issue', false);

        expect($cancelledJob->status)->toBe(JobStatus::Cancelled)
            ->and($cancelledJob->cancel_reason)->toBe('Payment issue')
            ->and($cancelledJob->cancelled_by)->toBe($admin->id);

        Event::assertDispatched(JobCancelled::class, 1);
    });

    it('ADM-T08 admin cancels in_progress job without refund by default', function () {
        Event::fake([JobCancelled::class]);

        $admin = User::factory()->admin()->create();
        $requester = User::factory()->user()->create();
        $seller = User::factory()->user()->create();

        $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

        $job = InspectionJob::factory()->create([
            'listing_id' => $listing->id,
            'requester_id' => $requester->id,
            'status' => JobStatus::InProgress,
        ]);

        $payment = Payment::factory()->create([
            'payer_id' => $requester->id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $job->id,
            'amount' => 50000,
            'status' => PaymentStatus::Success,
        ]);

        $ledgerMock = Mockery::mock(Ledger::class);
        // No refund should be called
        $ledgerMock->shouldNotReceive('refundPayment');

        app()->instance(Ledger::class, $ledgerMock);

        /** @var CancelJobByAdmin $action */
        $action = app(CancelJobByAdmin::class);

        $cancelledJob = $action->handle($admin, $job, 'Inspector unavailable', false);

        expect($cancelledJob->status)->toBe(JobStatus::Cancelled);
    });

    it('ADM-T08 admin cancels in_progress job with explicit refund', function () {
        Event::fake([JobCancelled::class]);

        $admin = User::factory()->admin()->create();
        $requester = User::factory()->user()->create();
        $seller = User::factory()->user()->create();

        $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

        $job = InspectionJob::factory()->create([
            'listing_id' => $listing->id,
            'requester_id' => $requester->id,
            'status' => JobStatus::InProgress,
        ]);

        $payment = Payment::factory()->create([
            'payer_id' => $requester->id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $job->id,
            'amount' => 50000,
            'status' => PaymentStatus::Success,
        ]);

        $ledgerMock = Mockery::mock(Ledger::class);
        $ledgerMock->shouldReceive('refundPayment')
            ->once()
            ->with($payment->id);

        app()->instance(Ledger::class, $ledgerMock);

        /** @var CancelJobByAdmin $action */
        $action = app(CancelJobByAdmin::class);

        $cancelledJob = $action->handle($admin, $job, 'Inspector illness', true);

        expect($cancelledJob->status)->toBe(JobStatus::Cancelled);
    });

    it('ADM-T08 admin cancels result_submitted job without refund by default', function () {
        Event::fake([JobCancelled::class]);

        $admin = User::factory()->admin()->create();
        $requester = User::factory()->user()->create();
        $inspector = User::factory()->inspector()->create();
        $seller = User::factory()->user()->create();

        $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

        $job = InspectionJob::factory()->create([
            'listing_id' => $listing->id,
            'requester_id' => $requester->id,
            'inspector_id' => $inspector->id,
            'status' => JobStatus::ResultSubmitted,
        ]);

        $payment = Payment::factory()->create([
            'payer_id' => $requester->id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $job->id,
            'amount' => 50000,
            'status' => PaymentStatus::Success,
        ]);

        $ledgerMock = Mockery::mock(Ledger::class);
        // No refund by default
        $ledgerMock->shouldNotReceive('refundPayment');

        app()->instance(Ledger::class, $ledgerMock);

        /** @var CancelJobByAdmin $action */
        $action = app(CancelJobByAdmin::class);

        $cancelledJob = $action->handle($admin, $job, 'Result rejected', false);

        expect($cancelledJob->status)->toBe(JobStatus::Cancelled);
    });

    it('ADM-T08 admin cancels result_submitted job with explicit refund', function () {
        Event::fake([JobCancelled::class]);

        $admin = User::factory()->admin()->create();
        $requester = User::factory()->user()->create();
        $inspector = User::factory()->inspector()->create();
        $seller = User::factory()->user()->create();

        $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

        $job = InspectionJob::factory()->create([
            'listing_id' => $listing->id,
            'requester_id' => $requester->id,
            'inspector_id' => $inspector->id,
            'status' => JobStatus::ResultSubmitted,
        ]);

        $payment = Payment::factory()->create([
            'payer_id' => $requester->id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $job->id,
            'amount' => 50000,
            'status' => PaymentStatus::Success,
        ]);

        $ledgerMock = Mockery::mock(Ledger::class);
        $ledgerMock->shouldReceive('refundPayment')
            ->once()
            ->with($payment->id);

        app()->instance(Ledger::class, $ledgerMock);

        /** @var CancelJobByAdmin $action */
        $action = app(CancelJobByAdmin::class);

        $cancelledJob = $action->handle($admin, $job, 'Result dispute', true);

        expect($cancelledJob->status)->toBe(JobStatus::Cancelled);
    });

    it('ADM-T08 admin cancel idempotent (ganda tidak berefek)', function () {
        Event::fake([JobCancelled::class]);

        $admin = User::factory()->admin()->create();
        $requester = User::factory()->user()->create();
        $seller = User::factory()->user()->create();

        $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

        $job = InspectionJob::factory()->create([
            'listing_id' => $listing->id,
            'requester_id' => $requester->id,
            'status' => JobStatus::Paid,
        ]);

        $payment = Payment::factory()->create([
            'payer_id' => $requester->id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $job->id,
            'amount' => 50000,
            'status' => PaymentStatus::Success,
        ]);

        $ledgerMock = Mockery::mock(Ledger::class);
        $ledgerMock->shouldReceive('refundPayment')
            ->once()
            ->with($payment->id);

        app()->instance(Ledger::class, $ledgerMock);

        /** @var CancelJobByAdmin $action */
        $action = app(CancelJobByAdmin::class);

        $first = $action->handle($admin, $job, 'Reason', false);
        expect($first->status)->toBe(JobStatus::Cancelled);

        // Second call should be idempotent
        $second = $action->handle($admin, $first, 'Reason', false);
        expect($second->status)->toBe(JobStatus::Cancelled);

        Event::assertDispatched(JobCancelled::class, 1);
    });
});
