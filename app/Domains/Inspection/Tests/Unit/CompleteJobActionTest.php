<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\CompleteJob;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
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

it('JOB-T15 completes job, sets completed_at, posts payout to ledger (once), and dispatches JobCompleted – idempoten ganda tidak berefek (JOB-E15)', function () {
    Event::fake([JobCompleted::class]);

    $t0 = Carbon::parse('2026-10-06 12:00:00', Clock::TIMEZONE);
    Carbon::setTestNow($t0);

    $user = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

    $job = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'inspector_id' => $inspector->id,
        'listing_id' => $listing->id,
        'status' => JobStatus::ResultSubmitted,
        'fee_amount' => 150000,
        'platform_fee_pct' => 20,
        'submitted_at' => $t0->copy()->subDays(3),
    ]);

    $payment = Payment::factory()->create([
        'payer_id' => $user->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'amount' => 150000,
        'status' => PaymentStatus::Success,
        'method' => PaymentMethod::Qris,
    ]);

    $ledgerMock = Mockery::mock(Ledger::class);
    $ledgerMock->shouldReceive('postInspectionPayout')
        ->once()
        ->with($payment->id, $inspector->id, 20);

    app()->instance(Ledger::class, $ledgerMock);
    $action = app(CompleteJob::class);

    $completedJob = $action->handle($job);

    expect($completedJob->status)->toBe(JobStatus::Completed)
        ->and($completedJob->completed_at->toIso8601String())->toBe($t0->toIso8601String());

    Event::assertDispatched(JobCompleted::class, function (JobCompleted $event) use ($job, $listing, $inspector, $user) {
        return $event->jobId === (string) $job->id
            && $event->listingId === (string) $listing->id
            && $event->inspectorId === (string) $inspector->id
            && $event->requesterId === (string) $user->id;
    });

    // Idempotent: panggil kedua kali tidak melempar galat dan tidak memposting ulang
    $again = $action->handle($completedJob);
    expect($again->status)->toBe(JobStatus::Completed);
});

it('rejects completing job from invalid statuses', function () {
    $user = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

    $job = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing->id,
        'status' => JobStatus::Paid,
    ]);

    /** @var CompleteJob $action */
    $action = app(CompleteJob::class);

    expect(fn () => $action->handle($job))
        ->toThrow(InvalidJobStatusTransitionException::class);
});
