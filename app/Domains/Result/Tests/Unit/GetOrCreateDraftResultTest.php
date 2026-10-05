<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Actions\GetOrCreateDraftResult;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Exceptions\JobNotInProgressException;
use App\Domains\Result\Exceptions\TemplateNotFoundException;
use App\Domains\Result\Exceptions\UnauthorizedInspectorException;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetOrCreateDraftResultTest extends TestCase
{
    use RefreshDatabase;

    private GetOrCreateDraftResult $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new GetOrCreateDraftResult();
    }

    public function test_RES_T01_creates_draft_result_from_template_for_car(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create([
            'listing_id' => \App\Domains\Listing\Models\Listing::factory()->active()->car()->create()->id,
        ]);

        InspectionTemplate::factory()->create([
            'vehicle_type' => 'car',
            'is_active' => true,
            'version' => 1,
        ]);

        $result = $this->action->handle($inspector, $job);

        $this->assertInstanceOf(InspectionResult::class, $result);
        $this->assertEquals(ResultStatus::Draft, $result->status);
        $this->assertEquals($job->id, $result->job_id);
        $this->assertEquals($inspector->id, $result->inspector_id);
    }

    public function test_RES_T01_creates_draft_result_from_template_for_motorcycle(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create([
            'listing_id' => \App\Domains\Listing\Models\Listing::factory()->active()->motorcycle()->create()->id,
        ]);

        InspectionTemplate::factory()->create([
            'vehicle_type' => 'motorcycle',
            'is_active' => true,
            'version' => 1,
        ]);

        $result = $this->action->handle($inspector, $job);

        $this->assertInstanceOf(InspectionResult::class, $result);
        $this->assertEquals(ResultStatus::Draft, $result->status);
    }

    public function test_RES_T01_returns_existing_draft_without_creating_duplicate(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->create(['vehicle_type' => 'car', 'is_active' => true, 'version' => 1]);

        $existing = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        $result = $this->action->handle($inspector, $job);

        $this->assertEquals($existing->id, $result->id);
        $this->assertEquals(1, InspectionResult::query()->where('job_id', $job->id)->count());
    }

    public function test_RES_T14_throws_when_inspector_is_not_assigned(): void
    {
        $inspector = User::factory()->inspector()->create();
        $otherInspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($otherInspector)->create();

        $this->expectException(UnauthorizedInspectorException::class);

        $this->action->handle($inspector, $job);
    }

    public function test_throws_when_job_is_not_in_progress(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->create([
            'inspector_id' => $inspector->id,
            'status' => JobStatus::Scheduled,
        ]);

        $this->expectException(JobNotInProgressException::class);

        $this->action->handle($inspector, $job);
    }

    public function test_throws_when_no_active_template_found(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create([
            'listing_id' => \App\Domains\Listing\Models\Listing::factory()->active()->car()->create()->id,
        ]);

        // No template seeded for 'car'
        $this->expectException(TemplateNotFoundException::class);

        $this->action->handle($inspector, $job);
    }

    public function test_RES_T15_draft_uses_template_version_at_creation_time(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create([
            'listing_id' => \App\Domains\Listing\Models\Listing::factory()->active()->car()->create()->id,
        ]);

        $templateV1 = InspectionTemplate::factory()->create([
            'vehicle_type' => 'car',
            'is_active' => true,
            'version' => 1,
        ]);

        $result = $this->action->handle($inspector, $job);

        $this->assertEquals($templateV1->id, $result->template_id);

        // Now deactivate v1 and create v2
        $templateV1->update(['is_active' => false]);
        InspectionTemplate::factory()->create([
            'vehicle_type' => 'car',
            'is_active' => true,
            'version' => 2,
        ]);

        // Fetching existing draft should return same template version
        $resultAgain = $this->action->handle($inspector, $job);
        $this->assertEquals($templateV1->id, $resultAgain->template_id);
    }
}
