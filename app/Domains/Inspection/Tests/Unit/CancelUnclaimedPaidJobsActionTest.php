<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\CancelUnclaimedPaidJobs;
use App\Domains\Inspection\Actions\TakeJob;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Exceptions\JobAlreadyTakenException;
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

it('JOB-T10 job paid 7 hari: refund dan listing active (6 hari belum, 7 hari sudah), idempoten (JOB-R8, JOB-E12)', function () {
    Event::fake([JobCancelled::class]);

    $t0 = Carbon::parse('2026-10-06 10:00:00', Clock::TIMEZONE);
    Carbon::setTestNow($t0);

    $user = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

    $job = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing->id,
        'status' => JobStatus::Paid,
        'fee_amount' => 150000,
        'platform_fee_pct' => 20,
        'available_since' => $t0,
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
    // Refund dipanggil tepat sekali
    $ledgerMock->shouldReceive('refundPayment')
        ->once()
        ->with($payment->id);

    $action = new CancelUnclaimedPaidJobs(
        settings: app(\App\Domains\Setting\Contracts\SettingsReader::class),
        ledger: $ledgerMock,
        events: app(\Illuminate\Contracts\Events\Dispatcher::class),
    );

    // 1. Pada 6 hari: belum dibatalkan (timeout 7 hari)
    Carbon::setTestNow($t0->copy()->addDays(6));
    $count = $action->handle();

    expect($count)->toBe(0)
        ->and($job->fresh()->status)->toBe(JobStatus::Paid);
    Event::assertNotDispatched(JobCancelled::class);

    // 2. Pada 7 hari + 1 detik: sudah dibatalkan
    Carbon::setTestNow($t0->copy()->addDays(7)->addSecond());
    $count = $action->handle();

    expect($count)->toBe(1);
    $freshJob = $job->fresh();
    expect($freshJob->status)->toBe(JobStatus::Cancelled)
        ->and($freshJob->cancelled_by)->toBeNull()
        ->and($freshJob->cancel_reason)->toContain('tidak diambil');

    Event::assertDispatched(JobCancelled::class, function (JobCancelled $event) use ($job, $listing) {
        return $event->jobId === (string) $job->id
            && $event->listingId === (string) $listing->id
            && $event->cancelledBy === null;
    });

    // 3. JOB-E12: Menjalankan kedua kali idempoten (tidak refund ulang, tidak memancarkan event ganda)
    $secondCount = $action->handle();
    expect($secondCount)->toBe(0)
        ->and($job->fresh()->status)->toBe(JobStatus::Cancelled);
});

it('handles race condition when inspector attempts to take job at cancellation time (JOB-E1, JOB-E8)', function () {
    $t0 = Carbon::parse('2026-10-06 10:00:00', Clock::TIMEZONE);
    Carbon::setTestNow($t0);

    $user = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    \App\Domains\Inspector\Models\InspectorProfile::factory()->approved()->create([
        'user_id' => $inspector->id,
        'vehicle_types' => [\App\Domains\Listing\Enums\VehicleType::Car->value],
    ]);

    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->create([
        'seller_id' => $seller->id,
        'vehicle_type' => \App\Domains\Listing\Enums\VehicleType::Car,
    ]);

    $job = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing->id,
        'status' => JobStatus::Paid,
        'available_since' => $t0,
    ]);

    Payment::factory()->create([
        'payer_id' => $user->id,
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $job->id,
        'status' => PaymentStatus::Success,
    ]);

    // Waktu 7 hari berlalu
    Carbon::setTestNow($t0->copy()->addDays(7)->addMinutes(1));

    /** @var CancelUnclaimedPaidJobs $cancelAction */
    $cancelAction = app(CancelUnclaimedPaidJobs::class);
    $cancelAction->handle();

    // Inspektor mencoba mengambil job yang sudah dibatalkan
    /** @var TakeJob $takeAction */
    $takeAction = app(TakeJob::class);

    expect(fn () => $takeAction->handle($inspector, $job))
        ->toThrow(JobAlreadyTakenException::class);
});
