<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Inspection\Enums\JobStatus;
use Tests\TestCase;

uses(TestCase::class);

it('has all 9 expected job status cases', function () {
    expect(JobStatus::Requested->value)->toBe('requested')
        ->and(JobStatus::Paid->value)->toBe('paid')
        ->and(JobStatus::Taken->value)->toBe('taken')
        ->and(JobStatus::Scheduled->value)->toBe('scheduled')
        ->and(JobStatus::InProgress->value)->toBe('in_progress')
        ->and(JobStatus::ResultSubmitted->value)->toBe('result_submitted')
        ->and(JobStatus::Completed->value)->toBe('completed')
        ->and(JobStatus::Cancelled->value)->toBe('cancelled')
        ->and(JobStatus::Expired->value)->toBe('expired');
});

it('returns labels, colors, and icons matching UI_GUIDELINES section 4', function (JobStatus $status, string $expectedColor, string $expectedIcon) {
    expect($status->label())->not->toBeEmpty();
    expect($status->color())->toBe($expectedColor);
    expect($status->icon())->toBe($expectedIcon);
})->with([
    [JobStatus::Requested, 'warning', 'jam'],
    [JobStatus::Paid, 'primary', 'jam-pasir'],
    [JobStatus::Taken, 'primary', 'orang'],
    [JobStatus::Scheduled, 'primary', 'kalender'],
    [JobStatus::InProgress, 'primary', 'kaca-pembesar'],
    [JobStatus::ResultSubmitted, 'warning', 'dokumen'],
    [JobStatus::Completed, 'success', 'centang'],
    [JobStatus::Cancelled, 'muted', 'silang'],
    [JobStatus::Expired, 'muted', 'jam'],
]);

/**
 * Full from-to transition matrix according to docs/flows/job.md section 1 diagram.
 * Every valid transition is accepted, every other pair is rejected.
 *
 * @return array<string, array{JobStatus, JobStatus, bool}>
 */
function jobStatusMatrixDataset(): array
{
    $valid = [
        JobStatus::Requested->value => [JobStatus::Paid, JobStatus::Expired, JobStatus::Cancelled],
        JobStatus::Paid->value => [JobStatus::Taken, JobStatus::Cancelled],
        JobStatus::Taken->value => [JobStatus::Scheduled, JobStatus::Paid, JobStatus::Cancelled],
        JobStatus::Scheduled->value => [JobStatus::InProgress, JobStatus::Paid, JobStatus::Cancelled],
        JobStatus::InProgress->value => [JobStatus::ResultSubmitted, JobStatus::Cancelled],
        JobStatus::ResultSubmitted->value => [JobStatus::Completed, JobStatus::Cancelled],
        JobStatus::Completed->value => [],
        JobStatus::Cancelled->value => [],
        JobStatus::Expired->value => [],
    ];

    $dataset = [];
    foreach (JobStatus::cases() as $from) {
        foreach (JobStatus::cases() as $to) {
            $expected = in_array($to, $valid[$from->value], true);
            $dataset["{$from->value} to {$to->value} is ".($expected ? 'valid' : 'invalid')] = [$from, $to, $expected];
        }
    }

    return $dataset;
}

it('JOB-T08 accepts every valid transition and rejects every other pair in the 9x9 matrix', function (JobStatus $from, JobStatus $to, bool $expected) {
    expect($from->canTransitionTo($to))->toBe($expected);
})->with(fn (): array => jobStatusMatrixDataset());

it('correctly reports isActive and isFinal states', function () {
    expect(JobStatus::Requested->isActive())->toBeTrue()
        ->and(JobStatus::Requested->isFinal())->toBeFalse()
        ->and(JobStatus::Paid->isActive())->toBeTrue()
        ->and(JobStatus::Paid->isFinal())->toBeFalse()
        ->and(JobStatus::Taken->isActive())->toBeTrue()
        ->and(JobStatus::Taken->isFinal())->toBeFalse()
        ->and(JobStatus::Scheduled->isActive())->toBeTrue()
        ->and(JobStatus::Scheduled->isFinal())->toBeFalse()
        ->and(JobStatus::InProgress->isActive())->toBeTrue()
        ->and(JobStatus::InProgress->isFinal())->toBeFalse()
        ->and(JobStatus::ResultSubmitted->isActive())->toBeTrue()
        ->and(JobStatus::ResultSubmitted->isFinal())->toBeFalse()
        ->and(JobStatus::Completed->isActive())->toBeFalse()
        ->and(JobStatus::Completed->isFinal())->toBeTrue()
        ->and(JobStatus::Cancelled->isActive())->toBeFalse()
        ->and(JobStatus::Cancelled->isFinal())->toBeTrue()
        ->and(JobStatus::Expired->isActive())->toBeFalse()
        ->and(JobStatus::Expired->isFinal())->toBeTrue();
});
