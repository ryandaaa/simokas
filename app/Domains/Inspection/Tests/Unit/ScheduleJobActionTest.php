<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\ScheduleJob;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobScheduled;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Exceptions\InvalidScheduleTimeException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Support\Clock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('schedules a taken job to scheduled status, saves scheduled_at in UTC, and dispatches JobScheduled', function () {
    Event::fake([JobScheduled::class]);

    $inspector = User::factory()->inspector()->create();
    $job = InspectionJob::factory()->taken($inspector)->create();

    $futureTime = now()->addDays(2)->setHour(14)->setMinute(0)->setSecond(0);

    /** @var ScheduleJob $action */
    $action = app(ScheduleJob::class);
    $result = $action->handle($inspector, $job, $futureTime);

    expect($result->status)->toBe(JobStatus::Scheduled)
        ->and($result->scheduled_at->toDateTimeString())->toBe($futureTime->copy()->setTimezone(Clock::TIMEZONE)->toDateTimeString());

    Event::assertDispatched(JobScheduled::class, function (JobScheduled $event) use ($job, $inspector) {
        return $event->jobId === $job->id
            && $event->listingId === $job->listing_id
            && $event->inspectorId === $inspector->id;
    });
});

it('rejects schedule time in the past or now', function () {
    $inspector = User::factory()->inspector()->create();
    $job = InspectionJob::factory()->taken($inspector)->create();

    $pastTime = now()->subHour();

    /** @var ScheduleJob $action */
    $action = app(ScheduleJob::class);

    expect(fn () => $action->handle($inspector, $job, $pastTime))
        ->toThrow(InvalidScheduleTimeException::class);
});

it('rejects scheduling by an inspector other than the assigned inspector', function () {
    $assignedInspector = User::factory()->inspector()->create();
    $otherInspector = User::factory()->inspector()->create();
    $job = InspectionJob::factory()->taken($assignedInspector)->create();

    /** @var ScheduleJob $action */
    $action = app(ScheduleJob::class);

    expect(fn () => $action->handle($otherInspector, $job, now()->addDay()))
        ->toThrow(InvalidJobStatusTransitionException::class);
});

it('rejects scheduling if job is not in taken status', function () {
    $inspector = User::factory()->inspector()->create();
    $job = InspectionJob::factory()->paid()->create(['inspector_id' => $inspector->id]);

    /** @var ScheduleJob $action */
    $action = app(ScheduleJob::class);

    expect(fn () => $action->handle($inspector, $job, now()->addDay()))
        ->toThrow(InvalidJobStatusTransitionException::class);
});
