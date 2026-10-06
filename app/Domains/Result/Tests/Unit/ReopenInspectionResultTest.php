<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Actions\ReopenInspectionResult;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Events\ResultReopened;
use App\Domains\Result\Exceptions\ReopenReasonRequiredException;
use App\Domains\Result\Models\InspectionResult;
use App\Support\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class ReopenInspectionResultTest extends TestCase
{
    use RefreshDatabase;

    private ReopenInspectionResult $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(ReopenInspectionResult::class);
    }

    public function test_res_t08_admin_can_reopen_submitted_result_and_result_locked_after_submit(): void
    {
        Event::fake([ResultReopened::class]);
        $action = app(ReopenInspectionResult::class);

        $admin = User::factory()->admin()->create();
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->create([
            'status' => JobStatus::ResultSubmitted,
            'inspector_id' => $inspector->id,
            'submitted_at' => now(),
        ]);

        $result = InspectionResult::factory()->submitted()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
        ]);

        $reopened = $action->handle($admin, $result, 'Perlu foto tambahan pada bodi depan');

        $this->assertSame(ResultStatus::Draft, $reopened->status);
        $this->assertNotNull($reopened->reopened_at);
        $this->assertSame($admin->id, $reopened->reopened_by);
        $this->assertSame('Perlu foto tambahan pada bodi depan', $reopened->reopen_reason);

        // Job status is updated to InProgress
        $this->assertSame(JobStatus::InProgress, $job->fresh()->status);

        // Event dispatched
        Event::assertDispatched(ResultReopened::class, function (ResultReopened $event) use ($result, $job, $admin, $inspector) {
            return $event->resultId === (string) $result->id
                && $event->jobId === (string) $job->id
                && $event->inspectorId === (string) $inspector->id
                && $event->reopenedBy === (string) $admin->id
                && $event->reason === 'Perlu foto tambahan pada bodi depan';
        });
    }

    public function test_res_t08_is_idempotent_when_result_already_draft(): void
    {
        Event::fake([ResultReopened::class]);
        $action = app(ReopenInspectionResult::class);

        $admin = User::factory()->admin()->create();
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);

        $reopened = $action->handle($admin, $result, 'Sudah draf');

        $this->assertSame(ResultStatus::Draft, $reopened->status);
        $this->assertSame(JobStatus::InProgress, $job->fresh()->status);

        // No event dispatched when already draft
        Event::assertNotDispatched(ResultReopened::class);
    }

    public function test_res_t08_requires_non_empty_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->create([
            'status' => JobStatus::ResultSubmitted,
            'inspector_id' => $inspector->id,
        ]);

        $result = InspectionResult::factory()->submitted()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);

        $this->expectException(ReopenReasonRequiredException::class);
        $this->action->handle($admin, $result, '   ');
    }

    public function test_res_t08_rejects_non_admin_users(): void
    {
        $inspector = User::factory()->inspector()->create();
        $user = User::factory()->user()->create();
        $job = InspectionJob::factory()->create([
            'status' => JobStatus::ResultSubmitted,
            'inspector_id' => $inspector->id,
        ]);

        $result = InspectionResult::factory()->submitted()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);

        $this->expectException(DomainException::class);
        $this->action->handle($inspector, $result, 'Alasan');
    }
}
