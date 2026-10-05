<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Support\Clock;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('registers all 4 inspection commands in the laravel scheduler with correct frequencies', function () {
    /** @var Schedule $schedule */
    $schedule = app(Schedule::class);

    $events = collect($schedule->events());

    $commandSignatures = $events->map(fn (Event $event) => $event->command);

    $expireJob = $events->first(fn (Event $event) => str_contains((string) $event->command, 'inspection:expire-requested-jobs'));
    $cancelJob = $events->first(fn (Event $event) => str_contains((string) $event->command, 'inspection:cancel-unclaimed-jobs'));
    $autoComplete = $events->first(fn (Event $event) => str_contains((string) $event->command, 'inspection:auto-complete-jobs'));
    $needsAttention = $events->first(fn (Event $event) => str_contains((string) $event->command, 'inspection:mark-needs-attention'));

    expect($expireJob)->not->toBeNull()
        ->and($expireJob->expression)->toBe('* * * * *')
        ->and($cancelJob)->not->toBeNull()
        ->and($cancelJob->expression)->toBe('0 0 * * *')
        ->and($autoComplete)->not->toBeNull()
        ->and($autoComplete->expression)->toBe('0 0 * * *')
        ->and($needsAttention)->not->toBeNull()
        ->and($needsAttention->expression)->toBe('0 * * * *');
});

it('executes inspection:expire-requested-jobs via artisan command', function () {
    $t0 = Carbon::parse('2026-10-06 10:00:00', Clock::TIMEZONE);
    Carbon::setTestNow($t0);

    $user = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $job = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing->id,
        'status' => JobStatus::Requested,
        'expires_at' => $t0->copy()->subMinute(),
    ]);

    $this->artisan('inspection:expire-requested-jobs')
        ->expectsOutputToContain('Berhasil mengedaluwarsakan 1 job requested.')
        ->assertSuccessful();

    expect($job->fresh()->status)->toBe(JobStatus::Expired);
});

it('executes inspection:cancel-unclaimed-jobs via artisan command', function () {
    $t0 = Carbon::parse('2026-10-06 10:00:00', Clock::TIMEZONE);
    Carbon::setTestNow($t0);

    $user = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

    $job = InspectionJob::factory()->create([
        'requester_id' => $user->id,
        'listing_id' => $listing->id,
        'status' => JobStatus::Paid,
        'available_since' => $t0->copy()->subDays(8),
    ]);

    $this->artisan('inspection:cancel-unclaimed-jobs')
        ->expectsOutputToContain('Berhasil membatalkan 1 job paid yang tidak diambil.')
        ->assertSuccessful();

    expect($job->fresh()->status)->toBe(JobStatus::Cancelled);
});

it('executes inspection:auto-complete-jobs via artisan command', function () {
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
        'submitted_at' => $t0->copy()->subDays(4),
    ]);

    $this->artisan('inspection:auto-complete-jobs')
        ->expectsOutputToContain('Berhasil menyelesaikan 1 job result_submitted.')
        ->assertSuccessful();

    expect($job->fresh()->status)->toBe(JobStatus::Completed);
});

it('executes inspection:mark-needs-attention via artisan command', function () {
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
        'taken_at' => $t0->copy()->subHours(50),
        'scheduled_at' => null,
        'needs_attention_at' => null,
    ]);

    $this->artisan('inspection:mark-needs-attention')
        ->expectsOutputToContain('Berhasil menandai 1 job yang memerlukan tindakan.')
        ->assertSuccessful();

    expect($job->fresh()->needs_attention_at)->not->toBeNull();
});
