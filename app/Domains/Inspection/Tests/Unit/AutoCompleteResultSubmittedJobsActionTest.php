<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\AutoCompleteResultSubmittedJobs;
use App\Domains\Inspection\Enums\JobStatus;
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

it('JOB-T11 job result_submitted 3 hari: completed dan fee cair (JOB-R9), idempoten', function () {
    Event::fake([JobCompleted::class]);

    $t0 = Carbon::parse('2026-10-06 10:00:00', Clock::TIMEZONE);
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
        'submitted_at' => $t0,
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
    // Payout dipanggil tepat sekali
    $ledgerMock->shouldReceive('postInspectionPayout')
        ->once()
        ->with($payment->id, $inspector->id, 20);

    $completeJob = new \App\Domains\Inspection\Actions\CompleteJob(
        ledger: $ledgerMock,
        events: app(\Illuminate\Contracts\Events\Dispatcher::class),
    );

    $action = new AutoCompleteResultSubmittedJobs(
        settings: app(\App\Domains\Setting\Contracts\SettingsReader::class),
        completeJob: $completeJob,
    );

    // 1. Pada 2 hari: belum selesai otomatis (auto_accept_days = 3)
    Carbon::setTestNow($t0->copy()->addDays(2));
    $count = $action->handle();

    expect($count)->toBe(0)
        ->and($job->fresh()->status)->toBe(JobStatus::ResultSubmitted);
    Event::assertNotDispatched(JobCompleted::class);

    // 2. Pada 3 hari + 1 detik: otomatis selesai
    Carbon::setTestNow($t0->copy()->addDays(3)->addSecond());
    $count = $action->handle();

    expect($count)->toBe(1);
    $fresh = $job->fresh();
    expect($fresh->status)->toBe(JobStatus::Completed)
        ->and($fresh->completed_at)->not->toBeNull();

    Event::assertDispatched(JobCompleted::class, function (JobCompleted $event) use ($job, $listing, $inspector) {
        return $event->jobId === (string) $job->id
            && $event->listingId === (string) $listing->id
            && $event->inspectorId === (string) $inspector->id;
    });

    // 3. JOB-E12: Menjalankan kedua kali tidak berefek ganda (idempoten)
    $secondCount = $action->handle();
    expect($secondCount)->toBe(0)
        ->and($job->fresh()->status)->toBe(JobStatus::Completed);
});
