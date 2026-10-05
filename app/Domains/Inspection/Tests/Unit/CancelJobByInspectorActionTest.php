<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\CancelJobByInspector;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Support\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('JOB-T07 cancels taken job by inspector: returns to paid, inspector_id null, available_since recalculated (JOB-E3, JOB-E16)', function () {
    $inspector = User::factory()->inspector()->create();
    $oldAvailableSince = now()->subDays(3);

    $job = InspectionJob::factory()->create([
        'status' => JobStatus::Taken,
        'inspector_id' => $inspector->id,
        'available_since' => $oldAvailableSince,
        'taken_at' => now()->subDay(),
    ]);

    /** @var CancelJobByInspector $action */
    $action = app(CancelJobByInspector::class);
    $result = $action->handle($inspector, $job, 'Penjual tidak dapat dihubungi');

    expect($result->status)->toBe(JobStatus::Paid)
        ->and($result->inspector_id)->toBeNull()
        ->and($result->scheduled_at)->toBeNull()
        ->and($result->cancel_reason)->toBe('Penjual tidak dapat dihubungi')
        ->and($result->available_since->toDateTimeString())->not->toBe($oldAvailableSince->toDateTimeString())
        ->and($result->available_since->isToday())->toBeTrue();
});

it('JOB-T07 cancels scheduled job by inspector: returns to paid, resets scheduled_at and recalculates available_since', function () {
    $inspector = User::factory()->inspector()->create();
    $oldAvailableSince = now()->subDays(2);

    $job = InspectionJob::factory()->create([
        'status' => JobStatus::Scheduled,
        'inspector_id' => $inspector->id,
        'available_since' => $oldAvailableSince,
        'taken_at' => now()->subDays(2),
        'scheduled_at' => now()->addDays(2),
    ]);

    /** @var CancelJobByInspector $action */
    $action = app(CancelJobByInspector::class);
    $result = $action->handle($inspector, $job, 'Jadwal bentrok darurat');

    expect($result->status)->toBe(JobStatus::Paid)
        ->and($result->inspector_id)->toBeNull()
        ->and($result->scheduled_at)->toBeNull()
        ->and($result->cancel_reason)->toBe('Jadwal bentrok darurat')
        ->and($result->available_since->toDateTimeString())->not->toBe($oldAvailableSince->toDateTimeString());
});

it('rejects cancellation without reason', function () {
    $inspector = User::factory()->inspector()->create();
    $job = InspectionJob::factory()->taken($inspector)->create();

    /** @var CancelJobByInspector $action */
    $action = app(CancelJobByInspector::class);

    expect(fn () => $action->handle($inspector, $job, '   '))
        ->toThrow(DomainException::class);
});

it('rejects cancellation by other inspector', function () {
    $assignedInspector = User::factory()->inspector()->create();
    $otherInspector = User::factory()->inspector()->create();
    $job = InspectionJob::factory()->taken($assignedInspector)->create();

    /** @var CancelJobByInspector $action */
    $action = app(CancelJobByInspector::class);

    expect(fn () => $action->handle($otherInspector, $job, 'Alasan'))
        ->toThrow(InvalidJobStatusTransitionException::class);
});

it('rejects inspector cancellation when status is in_progress', function () {
    $inspector = User::factory()->inspector()->create();
    $job = InspectionJob::factory()->inProgress($inspector)->create();

    /** @var CancelJobByInspector $action */
    $action = app(CancelJobByInspector::class);

    expect(fn () => $action->handle($inspector, $job, 'Ingin batal'))
        ->toThrow(InvalidJobStatusTransitionException::class);
});
