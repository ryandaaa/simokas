<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\StartInspection;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobStarted;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Models\InspectionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('starts scheduled inspection, transitions to in_progress, records started_at, and dispatches JobStarted', function () {
    Event::fake([JobStarted::class]);

    $inspector = User::factory()->inspector()->create();
    $job = InspectionJob::factory()->scheduled($inspector)->create();

    /** @var StartInspection $action */
    $action = app(StartInspection::class);
    $result = $action->handle($inspector, $job);

    expect($result->status)->toBe(JobStatus::InProgress)
        ->and($result->started_at)->not->toBeNull();

    Event::assertDispatched(JobStarted::class, function (JobStarted $event) use ($job, $inspector) {
        return $event->jobId === $job->id
            && $event->listingId === $job->listing_id
            && $event->inspectorId === $inspector->id;
    });
});

it('JOB-E10 rejects starting inspection before scheduled', function () {
    $inspector = User::factory()->inspector()->create();
    // Job is still in taken status (not scheduled yet)
    $job = InspectionJob::factory()->taken($inspector)->create();

    /** @var StartInspection $action */
    $action = app(StartInspection::class);

    expect(fn () => $action->handle($inspector, $job))
        ->toThrow(InvalidJobStatusTransitionException::class);
});

it('rejects starting inspection by an inspector other than the assigned inspector', function () {
    $assignedInspector = User::factory()->inspector()->create();
    $otherInspector = User::factory()->inspector()->create();
    $job = InspectionJob::factory()->scheduled($assignedInspector)->create();

    /** @var StartInspection $action */
    $action = app(StartInspection::class);

    expect(fn () => $action->handle($otherInspector, $job))
        ->toThrow(InvalidJobStatusTransitionException::class);
});
