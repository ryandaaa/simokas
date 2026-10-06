<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Actions\DeleteResultPhoto;
use App\Domains\Result\Actions\ReopenInspectionResult;
use App\Domains\Result\Actions\SaveDraftResult;
use App\Domains\Result\Actions\UploadResultPhoto;
use App\Domains\Result\DTOs\UploadPhotoData;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Exceptions\ResultAlreadySubmittedException;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class InspectionResultLockedActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_res_t08_locked_result_rejects_modifications_and_admin_reopens(): void
    {
        Storage::fake('private');

        $admin = User::factory()->admin()->create();
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->create([
            'status' => JobStatus::ResultSubmitted,
            'inspector_id' => $inspector->id,
            'submitted_at' => now(),
        ]);

        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'is_required' => true,
            'requires_photo' => true,
        ]);

        $result = InspectionResult::factory()->submitted()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
            'rating' => ItemRating::Good,
        ]);

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => "results/{$result->id}/photos/test.jpg",
        ]);
        Storage::disk('private')->put($photo->storage_path, 'fake-image-content');

        $saveDraft = app(SaveDraftResult::class);
        $uploadPhoto = app(UploadResultPhoto::class);
        $deletePhoto = app(DeleteResultPhoto::class);

        // 1. SaveDraftResult is rejected on submitted result
        try {
            $saveDraft->handle($inspector, $result, [
                $templateItem->id => ['rating' => 'fair', 'note' => 'Perubahan'],
            ]);
            $this->fail('Expected ResultAlreadySubmittedException for SaveDraftResult');
        } catch (ResultAlreadySubmittedException $e) {
            $this->assertSame(409, $e->getStatusCode());
            $this->assertSame('RESULT_ALREADY_SUBMITTED', $e->getErrorCode());
        }

        // 2. UploadResultPhoto is rejected on submitted result
        $fakeFile = UploadedFile::fake()->image('camera_photo.jpg', 640, 480);
        $dto = new UploadPhotoData(
            file: $fakeFile,
            templateItemId: $templateItem->id,
            stampedAt: now(),
            isCameraCapture: true,
        );

        try {
            $uploadPhoto->handle($inspector, $result, $dto);
            $this->fail('Expected ResultAlreadySubmittedException for UploadResultPhoto');
        } catch (ResultAlreadySubmittedException $e) {
            $this->assertSame(409, $e->getStatusCode());
            $this->assertSame('RESULT_ALREADY_SUBMITTED', $e->getErrorCode());
        }

        // 3. DeleteResultPhoto is rejected on submitted result
        try {
            $deletePhoto->handle($inspector, $result, $photo);
            $this->fail('Expected ResultAlreadySubmittedException for DeleteResultPhoto');
        } catch (ResultAlreadySubmittedException $e) {
            $this->assertSame(409, $e->getStatusCode());
            $this->assertSame('RESULT_ALREADY_SUBMITTED', $e->getErrorCode());
        }

        // 4. Admin reopens the locked result (RES-E14)
        $reopenAction = app(ReopenInspectionResult::class);
        $reopened = $reopenAction->handle($admin, $result, 'Perlu perbaikan catatan.');

        $this->assertSame(ResultStatus::Draft, $reopened->status);
        $this->assertSame(JobStatus::InProgress, $job->fresh()->status);

        // 5. Now SaveDraftResult succeeds!
        $updated = $saveDraft->handle($inspector, $reopened, [
            $templateItem->id => ['rating' => 'fair', 'note' => 'Catatan diperbaiki'],
        ]);
        $this->assertSame(ResultStatus::Draft, $updated->status);
        $this->assertSame('fair', $updated->items->firstWhere('template_item_id', $templateItem->id)->rating->value);

        // 6. DeleteResultPhoto succeeds!
        $deletePhoto->handle($inspector, $reopened, $photo);
        $this->assertDatabaseMissing('result_photos', ['id' => $photo->id]);
    }
}
