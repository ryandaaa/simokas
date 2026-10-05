<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Policies\InspectionResultPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InspectionResultPolicyTest extends TestCase
{
    use RefreshDatabase;

    private InspectionResultPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new InspectionResultPolicy;
    }

    public function test_it_authorizes_fill_only_for_assigned_inspector_on_in_progress_job(): void
    {
        $inspector = User::factory()->inspector()->create();
        $otherInspector = User::factory()->inspector()->create();
        $user = User::factory()->user()->create();
        $admin = User::factory()->admin()->create();

        $job = InspectionJob::factory()->inProgress($inspector)->create();

        $this->assertTrue($this->policy->fill($inspector, $job));
        $this->assertFalse($this->policy->fill($otherInspector, $job));
        $this->assertFalse($this->policy->fill($user, $job));
        $this->assertFalse($this->policy->fill($admin, $job));

        // When job is not in progress
        $job->update(['status' => JobStatus::Scheduled]);
        $this->assertFalse($this->policy->fill($inspector, $job->refresh()));

        // When inspector is blocked
        $inspector->update(['status' => UserStatus::Blocked]);
        $job->update(['status' => JobStatus::InProgress]);
        $this->assertFalse($this->policy->fill($inspector->refresh(), $job->refresh()));
    }

    public function test_it_authorizes_view_draft_only_for_assigned_inspector(): void
    {
        $inspector = User::factory()->inspector()->create();
        $otherInspector = User::factory()->inspector()->create();

        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);

        $this->assertTrue($this->policy->viewDraft($inspector, $result));
        $this->assertFalse($this->policy->viewDraft($otherInspector, $result));

        // When status is submitted
        $result->update(['status' => ResultStatus::Submitted]);
        $this->assertFalse($this->policy->viewDraft($inspector, $result->refresh()));
    }

    public function test_it_authorizes_update_draft_only_when_job_is_in_progress(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);

        $this->assertTrue($this->policy->updateDraft($inspector, $result));

        $job->update(['status' => JobStatus::Completed]);
        $this->assertFalse($this->policy->updateDraft($inspector, $result->refresh()));
    }

    public function test_dataset_roles_and_job_statuses_for_policy_actions(): void
    {
        $assignedInspector = User::factory()->inspector()->create();
        $otherInspector = User::factory()->inspector()->create();
        $user = User::factory()->user()->create();
        $admin = User::factory()->admin()->create();

        $allRoles = [
            'assigned_inspector' => $assignedInspector,
            'other_inspector' => $otherInspector,
            'user' => $user,
            'admin' => $admin,
        ];

        $template = InspectionTemplate::factory()->create();

        foreach (JobStatus::cases() as $jobStatus) {
            $job = InspectionJob::factory()->create([
                'inspector_id' => $assignedInspector->id,
                'status' => $jobStatus,
            ]);

            $result = InspectionResult::factory()->draft()->create([
                'job_id' => $job->id,
                'inspector_id' => $assignedInspector->id,
                'template_id' => $template->id,
            ]);

            foreach ($allRoles as $roleName => $testUser) {
                $canFill = $this->policy->fill($testUser, $job);
                $expectedFill = ($roleName === 'assigned_inspector' && $jobStatus === JobStatus::InProgress);
                $this->assertSame($expectedFill, $canFill, "Failed fill for {$roleName} with job status {$jobStatus->value}");

                $canUpdate = $this->policy->updateDraft($testUser, $result);
                $expectedUpdate = ($roleName === 'assigned_inspector' && $jobStatus === JobStatus::InProgress);
                $this->assertSame($expectedUpdate, $canUpdate, "Failed updateDraft for {$roleName} with job status {$jobStatus->value}");

                $canView = $this->policy->viewDraft($testUser, $result);
                $expectedView = ($roleName === 'assigned_inspector');
                $this->assertSame($expectedView, $canView, "Failed viewDraft for {$roleName} with job status {$jobStatus->value}");
            }
        }
    }
}
