<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Inspection\Contracts\InspectionJobResultContract;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspection\Services\InspectionJobResultService;
use App\Support\Clock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InspectionJobResultServiceTest extends TestCase
{
    use RefreshDatabase;

    private InspectionJobResultService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InspectionJobResultService::class);
    }

    public function test_it_is_bound_to_contract_in_container(): void
    {
        $resolved = app(InspectionJobResultContract::class);
        $this->assertInstanceOf(InspectionJobResultService::class, $resolved);
    }

    public function test_it_marks_job_result_submitted_from_in_progress(): void
    {
        $job = InspectionJob::factory()->inProgress()->create();
        $frozenTime = Clock::now();

        $updatedJob = $this->service->markResultSubmitted($job->id);

        $this->assertSame(JobStatus::ResultSubmitted, $updatedJob->status);
        $this->assertNotNull($updatedJob->submitted_at);
        $this->assertSame(
            $frozenTime->format('Y-m-d H:i:s'),
            $updatedJob->submitted_at->format('Y-m-d H:i:s')
        );
        $this->assertSame(JobStatus::ResultSubmitted, $job->fresh()->status);
    }

    public function test_it_is_idempotent_when_marking_result_submitted(): void
    {
        $job = InspectionJob::factory()->create([
            'status' => JobStatus::ResultSubmitted,
            'submitted_at' => Clock::now(),
        ]);

        $resultJob = $this->service->markResultSubmitted($job->id);

        $this->assertSame(JobStatus::ResultSubmitted, $resultJob->status);
    }

    public function test_it_rejects_marking_result_submitted_from_invalid_status(): void
    {
        $job = InspectionJob::factory()->paid()->create();

        $this->expectException(InvalidJobStatusTransitionException::class);
        $this->service->markResultSubmitted($job->id);
    }

    public function test_it_reopens_job_to_in_progress_from_result_submitted(): void
    {
        $job = InspectionJob::factory()->create([
            'status' => JobStatus::ResultSubmitted,
            'submitted_at' => Clock::now(),
        ]);

        $reopenedJob = $this->service->reopenJob($job->id);

        $this->assertSame(JobStatus::InProgress, $reopenedJob->status);
        $this->assertSame(JobStatus::InProgress, $job->fresh()->status);
    }

    public function test_it_is_idempotent_when_reopening_job(): void
    {
        $job = InspectionJob::factory()->inProgress()->create();

        $resultJob = $this->service->reopenJob($job->id);

        $this->assertSame(JobStatus::InProgress, $resultJob->status);
    }

    public function test_it_rejects_reopening_job_from_invalid_status(): void
    {
        $job = InspectionJob::factory()->paid()->create();

        $this->expectException(InvalidJobStatusTransitionException::class);
        $this->service->reopenJob($job->id);
    }
}
