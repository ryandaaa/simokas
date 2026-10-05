<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Actions\DeleteResultPhoto;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Exceptions\JobNotInProgressException;
use App\Domains\Result\Exceptions\PhotoUploadException;
use App\Domains\Result\Exceptions\ResultAlreadySubmittedException;
use App\Domains\Result\Exceptions\UnauthorizedInspectorException;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DeleteResultPhotoTest extends TestCase
{
    use RefreshDatabase;

    private DeleteResultPhoto $action;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->action = new DeleteResultPhoto;
    }

    public function test_deletes_photo_and_removes_file_from_storage(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
        ]);

        $path = "results/{$result->id}/photos/test_photo.jpg";
        Storage::disk('private')->put($path, 'dummy image content');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $this->action->handle($inspector, $result, $photo);

        Storage::disk('private')->assertMissing($path);
        $this->assertDatabaseMissing('result_photos', ['id' => $photo->id]);
    }

    public function test_forbids_unauthorized_inspector(): void
    {
        $inspector = User::factory()->inspector()->create();
        $otherInspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
        ]);

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
        ]);

        $this->expectException(UnauthorizedInspectorException::class);
        $this->action->handle($otherInspector, $result, $photo);
    }

    public function test_forbids_when_job_not_in_progress(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->create([
            'inspector_id' => $inspector->id,
            'status' => JobStatus::Completed,
        ]);
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
        ]);

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
        ]);

        $this->expectException(JobNotInProgressException::class);
        $this->action->handle($inspector, $result, $photo);
    }

    public function test_forbids_when_result_already_submitted(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $result = InspectionResult::factory()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Submitted,
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
        ]);

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
        ]);

        $this->expectException(ResultAlreadySubmittedException::class);
        $this->action->handle($inspector, $result, $photo);
    }

    public function test_forbids_deleting_photo_of_another_result(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $result1 = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        $resultItem1 = ResultItem::factory()->create([
            'result_id' => $result1->id,
            'template_item_id' => $templateItem->id,
        ]);

        $otherPhoto = ResultPhoto::factory()->create([
            'result_id' => $result1->id,
            'result_item_id' => $resultItem1->id,
        ]);
        $otherPhoto->result_id = '00000000-0000-0000-0000-000000000000';

        $this->expectException(PhotoUploadException::class);
        $this->action->handle($inspector, $result1, $otherPhoto);
    }
}
