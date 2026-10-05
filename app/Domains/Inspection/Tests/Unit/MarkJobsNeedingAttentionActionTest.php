<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\MarkJobsNeedingAttention;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Support\Clock;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('JOB-T12 job taken tanpa jadwal 48 jam ditandai needs_attention_at (JOB-R10)', function () {
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
        'status' => JobStatus::Taken,
        'taken_at' => $t0,
        'scheduled_at' => null,
        'needs_attention_at' => null,
    ]);

    /** @var MarkJobsNeedingAttention $action */
    $action = app(MarkJobsNeedingAttention::class);

    // 1. Pada 47 jam: belum ditandai
    Carbon::setTestNow($t0->copy()->addHours(47));
    $count = $action->handle();

    expect($count)->toBe(0)
        ->and($job->fresh()->needs_attention_at)->toBeNull();

    // 2. Pada 49 jam: ditandai needs_attention_at
    Carbon::setTestNow($t0->copy()->addHours(49));
    $count = $action->handle();

    expect($count)->toBe(1);
    $fresh = $job->fresh();
    expect($fresh->needs_attention_at)->not->toBeNull()
        ->and($fresh->needs_attention_at->toIso8601String())->toBe(Clock::now()->toIso8601String());

    // 3. Idempoten: dijalankan lagi tidak menandai ulang
    $secondCount = $action->handle();
    expect($secondCount)->toBe(0)
        ->and($job->fresh()->needs_attention_at->toIso8601String())->toBe($fresh->needs_attention_at->toIso8601String());
});

it('does not mark taken job if it already has a scheduled_at time', function () {
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
        'status' => JobStatus::Taken,
        'taken_at' => $t0,
        'scheduled_at' => $t0->copy()->addDays(3),
        'needs_attention_at' => null,
    ]);

    Carbon::setTestNow($t0->copy()->addHours(50));

    /** @var MarkJobsNeedingAttention $action */
    $action = app(MarkJobsNeedingAttention::class);
    $count = $action->handle();

    expect($count)->toBe(0)
        ->and($job->fresh()->needs_attention_at)->toBeNull();
});

it('marks scheduled job when scheduled time has passed without being started', function () {
    $t0 = Carbon::parse('2026-10-06 10:00:00', Clock::TIMEZONE);
    Carbon::setTestNow($t0);

    $user = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

    $scheduledTime = $t0->copy()->addDay();

    $job = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'inspector_id' => $inspector->id,
        'listing_id' => $listing->id,
        'status' => JobStatus::Scheduled,
        'scheduled_at' => $scheduledTime,
        'needs_attention_at' => null,
    ]);

    /** @var MarkJobsNeedingAttention $action */
    $action = app(MarkJobsNeedingAttention::class);

    // Sebelum jadwal: belum ditandai
    Carbon::setTestNow($scheduledTime->copy()->subHour());
    expect($action->handle())->toBe(0)
        ->and($job->fresh()->needs_attention_at)->toBeNull();

    // Setelah jadwal terlewati tanpa dimulai: ditandai
    Carbon::setTestNow($scheduledTime->copy()->addHour());
    expect($action->handle())->toBe(1);

    $fresh = $job->fresh();
    expect($fresh->needs_attention_at)->not->toBeNull()
        ->and($fresh->needs_attention_at->toIso8601String())->toBe(Clock::now()->toIso8601String());
});
