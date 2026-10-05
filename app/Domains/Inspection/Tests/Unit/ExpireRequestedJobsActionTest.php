<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\ExpireRequestedJobs;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobExpired;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Contracts\Billing;
use App\Support\Clock;
use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('JOB-T09 tagihan kedaluwarsa tepat di 30 menit (29 menit belum, 31 menit sudah)', function () {
    Event::fake([JobExpired::class]);

    $t0 = Carbon::parse('2026-10-06 10:00:00', Clock::TIMEZONE);
    Carbon::setTestNow($t0);

    $user = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $job = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing->id,
        'status' => JobStatus::Requested,
        'expires_at' => $t0->copy()->addMinutes(30),
    ]);

    /** @var ExpireRequestedJobs $action */
    $action = app(ExpireRequestedJobs::class);

    // 1. Pada 29 menit: belum kedaluwarsa
    Carbon::setTestNow($t0->copy()->addMinutes(29));
    $expiredCount = $action->handle();

    expect($expiredCount)->toBe(0)
        ->and($job->fresh()->status)->toBe(JobStatus::Requested);
    Event::assertNotDispatched(JobExpired::class);

    // 2. Pada 31 menit: sudah kedaluwarsa
    Carbon::setTestNow($t0->copy()->addMinutes(31));
    $expiredCount = $action->handle();

    expect($expiredCount)->toBe(1)
        ->and($job->fresh()->status)->toBe(JobStatus::Expired);

    Event::assertDispatched(JobExpired::class, function (JobExpired $event) use ($job, $listing, $user) {
        return $event->jobId === (string) $job->id
            && $event->listingId === (string) $listing->id
            && $event->requesterId === (string) $user->id;
    });

    // 3. JOB-E12: Dijalankan kedua kali bersifat idempoten
    $secondCount = $action->handle();
    expect($secondCount)->toBe(0)
        ->and($job->fresh()->status)->toBe(JobStatus::Expired);
});

it('does not expire non-requested jobs even if expires_at has passed', function () {
    Event::fake([JobExpired::class]);

    $t0 = Carbon::parse('2026-10-06 10:00:00', Clock::TIMEZONE);
    Carbon::setTestNow($t0);

    $user = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $paidJob = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing->id,
        'status' => JobStatus::Paid,
        'expires_at' => $t0->copy()->subMinutes(10),
    ]);

    /** @var ExpireRequestedJobs $action */
    $action = app(ExpireRequestedJobs::class);
    $expiredCount = $action->handle();

    expect($expiredCount)->toBe(0)
        ->and($paidJob->fresh()->status)->toBe(JobStatus::Paid);
    Event::assertNotDispatched(JobExpired::class);
});
