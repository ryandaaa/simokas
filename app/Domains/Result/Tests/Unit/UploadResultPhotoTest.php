<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Actions\GetOrCreateDraftResult;
use App\Domains\Result\Actions\UploadResultPhoto;
use App\Domains\Result\DTOs\UploadPhotoData;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class UploadResultPhotoTest extends TestCase
{
    use RefreshDatabase;

    private UploadResultPhoto $action;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');

        $this->action = new UploadResultPhoto(
            new GetOrCreateDraftResult
        );
    }

    public function test_RES_T04_uploads_camera_photo_with_server_captured_at_and_stamped_at(): void
    {
        Carbon::setTestNow('2026-10-06 12:00:00');

        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'requires_photo' => true,
        ]);

        $stampedAt = Carbon::parse('2026-10-06 11:59:50');
        $file = UploadedFile::fake()->image('camera_photo.jpg', 640, 480);

        $dto = new UploadPhotoData(
            file: $file,
            templateItemId: $templateItem->id,
            stampedAt: $stampedAt,
            isCameraCapture: true,
        );

        $photo = $this->action->handle($inspector, $job, $dto);

        $this->assertInstanceOf(ResultPhoto::class, $photo);
        $this->assertTrue($photo->is_camera_capture);
        $this->assertEquals(Carbon::now(), $photo->captured_at);
        $this->assertEquals($stampedAt, $photo->stamped_at);

        // Stored in private disk only, never public
        Storage::disk('private')->assertExists($photo->storage_path);
        Storage::disk('public')->assertMissing($photo->storage_path);

        // Random filename, not original client name
        $this->assertStringNotContainsString('camera_photo.jpg', $photo->storage_path);

        $this->assertDatabaseHas('result_photos', [
            'id' => $photo->id,
            'storage_path' => $photo->storage_path,
            'is_camera_capture' => true,
        ]);
    }

    public function test_RES_T04_rejects_non_camera_photo_with_422_NOT_CAMERA_CAPTURE(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $file = UploadedFile::fake()->image('gallery_photo.jpg');

        $dto = new UploadPhotoData(
            file: $file,
            templateItemId: $templateItem->id,
            stampedAt: now(),
            isCameraCapture: false, // from gallery / not camera
        );

        try {
            $this->action->handle($inspector, $job, $dto);
            $this->fail('Expected PhotoUploadException was not thrown.');
        } catch (PhotoUploadException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertSame('NOT_CAMERA_CAPTURE', $e->getErrorCode());
        }
    }

    public function test_RES_T05_rejects_file_exceeding_2_mb(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        // 2049 KB > 2048 KB (2 MB)
        $largeFile = UploadedFile::fake()->create('large.jpg', 2049, 'image/jpeg');

        $dto = new UploadPhotoData(
            file: $largeFile,
            templateItemId: $templateItem->id,
            stampedAt: now(),
            isCameraCapture: true,
        );

        try {
            $this->action->handle($inspector, $job, $dto);
            $this->fail('Expected PhotoUploadException was not thrown.');
        } catch (PhotoUploadException $e) {
            $this->assertSame(413, $e->getStatusCode());
            $this->assertSame('FILE_TOO_LARGE', $e->getErrorCode());
        }
    }

    public function test_RES_T05_rejects_non_image_or_unsupported_mime(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $pdfFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $dto = new UploadPhotoData(
            file: $pdfFile,
            templateItemId: $templateItem->id,
            stampedAt: now(),
            isCameraCapture: true,
        );

        try {
            $this->action->handle($inspector, $job, $dto);
            $this->fail('Expected PhotoUploadException was not thrown.');
        } catch (PhotoUploadException $e) {
            $this->assertSame(415, $e->getStatusCode());
            $this->assertSame('UNSUPPORTED_TYPE', $e->getErrorCode());
        }
    }

    public function test_retake_replaces_old_photo_and_removes_old_file_from_storage(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $firstFile = UploadedFile::fake()->image('first.jpg');
        $dto1 = new UploadPhotoData($firstFile, $templateItem->id, now(), true);
        $photo1 = $this->action->handle($inspector, $job, $dto1);

        Storage::disk('private')->assertExists($photo1->storage_path);

        // Upload second photo for same item (retake)
        $secondFile = UploadedFile::fake()->image('second.png');
        $dto2 = new UploadPhotoData($secondFile, $templateItem->id, now(), true);
        $photo2 = $this->action->handle($inspector, $job, $dto2);

        // Old file deleted, new file exists
        Storage::disk('private')->assertMissing($photo1->storage_path);
        Storage::disk('private')->assertExists($photo2->storage_path);

        $this->assertDatabaseMissing('result_photos', ['id' => $photo1->id]);
        $this->assertDatabaseHas('result_photos', ['id' => $photo2->id]);
    }

    public function test_forbids_unauthorized_inspector(): void
    {
        $inspector = User::factory()->inspector()->create();
        $otherInspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $file = UploadedFile::fake()->image('photo.jpg');
        $dto = new UploadPhotoData($file, $templateItem->id, now(), true);

        $this->expectException(UnauthorizedInspectorException::class);
        $this->action->handle($otherInspector, $job, $dto);
    }

    public function test_forbids_upload_when_job_not_in_progress(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->create([
            'inspector_id' => $inspector->id,
            'status' => JobStatus::Scheduled,
        ]);
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $file = UploadedFile::fake()->image('photo.jpg');
        $dto = new UploadPhotoData($file, $templateItem->id, now(), true);

        $this->expectException(JobNotInProgressException::class);
        $this->action->handle($inspector, $job, $dto);
    }

    public function test_forbids_upload_when_result_already_submitted(): void
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

        $file = UploadedFile::fake()->image('photo.jpg');
        $dto = new UploadPhotoData($file, $templateItem->id, now(), true);

        $this->expectException(ResultAlreadySubmittedException::class);
        $this->action->handle($inspector, $result, $dto);
    }
}
