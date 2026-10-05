<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\AutoCompleteResultSubmittedJobs;
use App\Domains\Inspection\Actions\CancelUnclaimedPaidJobs;
use App\Domains\Inspection\Actions\ExpireRequestedJobs;
use App\Domains\Inspection\Actions\MarkJobsNeedingAttention;
use App\Domains\Inspection\Actions\RequestInspection;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Support\Clock;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('integrates expired requested job, clears active_listing_id, and allows new inspection request', function () {
    $t0 = Carbon::parse('2026-10-06 10:00:00', Clock::TIMEZONE);
    Carbon::setTestNow($t0);

    $user = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'vehicle_type' => VehicleType::Car,
    ]);

    /** @var RequestInspection $requestAction */
    $requestAction = app(RequestInspection::class);
    $result1 = $requestAction->handle($user, $listing);

    $job1 = $result1['job'];
    expect($job1->status)->toBe(JobStatus::Requested)
        ->and($listing->fresh()->status)->toBe(ListingStatus::Active);

    // Waktu 31 menit berlalu
    Carbon::setTestNow($t0->copy()->addMinutes(31));

    /** @var ExpireRequestedJobs $expireAction */
    $expireAction = app(ExpireRequestedJobs::class);
    $expiredCount = $expireAction->handle();

    expect($expiredCount)->toBe(1)
        ->and($job1->fresh()->status)->toBe(JobStatus::Expired);

    // Listing dapat diajukan inspeksi baru karena job lama sudah expired (active_listing_id null)
    $newUser = User::factory()->user()->create();
    $result2 = $requestAction->handle($newUser, $listing);

    expect($result2['job']->id)->not->toBe($job1->id)
        ->and($result2['job']->status)->toBe(JobStatus::Requested)
        ->and($result2['reused'])->toBeFalse();
});

it('integrates unclaimed paid job cancellation: real refund via Ledger and real listing returns to active', function () {
    $t0 = Carbon::parse('2026-10-06 10:00:00', Clock::TIMEZONE);
    Carbon::setTestNow($t0);

    $user = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->create([
        'seller_id' => $seller->id,
        'vehicle_type' => VehicleType::Car,
    ]);

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

    // 7 hari dan 1 menit berlalu
    Carbon::setTestNow($t0->copy()->addDays(7)->addMinute());

    /** @var CancelUnclaimedPaidJobs $action */
    $action = app(CancelUnclaimedPaidJobs::class);
    $count = $action->handle();

    expect($count)->toBe(1);

    // 1. Status job menjadi cancelled
    expect($job->fresh()->status)->toBe(JobStatus::Cancelled);

    // 2. Listing kembali active via event listener nyata (ReturnListingToActiveOnJobCancelled)
    expect($listing->fresh()->status)->toBe(ListingStatus::Active);

    // 3. Payment di-refund 100% lewat Ledger nyata
    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);

    $ledgerEntries = LedgerEntry::query()->where('payment_id', $payment->id)->get();
    expect($ledgerEntries)->toHaveCount(1)
        ->and($ledgerEntries->first()->entry_type)->toBe(LedgerEntryType::Refund)
        ->and($ledgerEntries->first()->amount)->toBe(150000);

    // 4. Idempoten: dijalankan lagi tidak menghasilkan entri baru
    $secondCount = $action->handle();
    expect($secondCount)->toBe(0)
        ->and(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);
});

it('integrates auto-complete job: real fee payout in Ledger and listing inspected badge + active', function () {
    $t0 = Carbon::parse('2026-10-06 10:00:00', Clock::TIMEZONE);
    Carbon::setTestNow($t0);

    $user = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    \App\Domains\Inspector\Models\InspectorProfile::factory()->approved()->create([
        'user_id' => $inspector->id,
        'vehicle_types' => [VehicleType::Car->value],
    ]);

    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->create([
        'seller_id' => $seller->id,
        'vehicle_type' => VehicleType::Car,
        'is_inspected' => false,
        'last_inspected_at' => null,
    ]);

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

    // 3 hari dan 1 menit berlalu
    Carbon::setTestNow($t0->copy()->addDays(3)->addMinute());

    /** @var AutoCompleteResultSubmittedJobs $action */
    $action = app(AutoCompleteResultSubmittedJobs::class);
    $count = $action->handle();

    expect($count)->toBe(1);

    // 1. Status job menjadi completed
    $freshJob = $job->fresh();
    expect($freshJob->status)->toBe(JobStatus::Completed)
        ->and($freshJob->completed_at)->not->toBeNull();

    // 2. Listing mendapat lencana is_inspected = true dan kembali active via event listener nyata
    $freshListing = $listing->fresh();
    expect($freshListing->status)->toBe(ListingStatus::Active)
        ->and($freshListing->is_inspected)->toBeTrue()
        ->and($freshListing->last_inspected_at)->not->toBeNull();

    // 3. Fee diposting ke Ledger nyata (inspektor 80% = Rp120.000, platform 20% = Rp30.000)
    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();
    expect($entries)->toHaveCount(2);

    $inspectorEntry = $entries->firstWhere('entry_type', LedgerEntryType::InspectionFee);
    $platformEntry = $entries->firstWhere('entry_type', LedgerEntryType::PlatformFee);

    expect($inspectorEntry)->not->toBeNull()
        ->and($inspectorEntry->amount)->toBe(120000)
        ->and($inspectorEntry->account_user_id)->toBe($inspector->id)
        ->and($platformEntry)->not->toBeNull()
        ->and($platformEntry->amount)->toBe(30000)
        ->and($platformEntry->account_user_id)->toBeNull();

    // 4. Idempoten: dijalankan lagi tidak memposting ulang entri ledger
    $secondCount = $action->handle();
    expect($secondCount)->toBe(0)
        ->and(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(2);
});

it('integrates mark jobs needing attention for both taken without schedule and scheduled past time', function () {
    $t0 = Carbon::parse('2026-10-06 10:00:00', Clock::TIMEZONE);
    Carbon::setTestNow($t0);

    $user = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $seller = User::factory()->user()->create();

    $listing1 = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);
    $listing2 = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

    // Job 1: taken tanpa jadwal, sudah 50 jam
    $job1 = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'inspector_id' => $inspector->id,
        'listing_id' => $listing1->id,
        'status' => JobStatus::Taken,
        'taken_at' => $t0->copy()->subHours(50),
        'scheduled_at' => null,
        'needs_attention_at' => null,
    ]);

    // Job 2: scheduled, jadwal 2 jam yang lalu tapi belum in_progress
    $job2 = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'inspector_id' => $inspector->id,
        'listing_id' => $listing2->id,
        'status' => JobStatus::Scheduled,
        'scheduled_at' => $t0->copy()->subHours(2),
        'needs_attention_at' => null,
    ]);

    /** @var MarkJobsNeedingAttention $action */
    $action = app(MarkJobsNeedingAttention::class);
    $count = $action->handle();

    expect($count)->toBe(2)
        ->and($job1->fresh()->needs_attention_at)->not->toBeNull()
        ->and($job2->fresh()->needs_attention_at)->not->toBeNull();

    // Idempoten
    expect($action->handle())->toBe(0);
});
