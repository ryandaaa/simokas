<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Models\InspectorProfile;
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

final class UploadResultPhotoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');
    }

    private function createVerifiedInspector(): User
    {
        $inspector = User::factory()->inspector()->create();
        InspectorProfile::factory()->approved()->create([
            'user_id' => $inspector->id,
            'vehicle_types' => ['car', 'motorcycle'],
        ]);

        return $inspector;
    }

    public function test_re_s_t04_uploads_camera_photo_successfully_with_server_captured_at(): void
    {
        Carbon::setTestNow('2026-10-06 14:30:00');

        $inspector = $this->createVerifiedInspector();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'requires_photo' => true,
        ]);

        $file = UploadedFile::fake()->image('camera_shot.jpg', 800, 600);
        $stampedAt = '2026-10-06T14:29:50+07:00';

        $response = $this->actingAs($inspector)->postJson(
            route('result.photos.upload', ['job' => $job->id]),
            [
                'file' => $file,
                'template_item_id' => $templateItem->id,
                'stamped_at' => $stampedAt,
                'is_camera_capture' => true,
            ]
        );

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'photo_id',
                'captured_at',
            ],
            'error',
        ]);

        $photoId = $response->json('data.photo_id');
        $this->assertNotEmpty($photoId);

        /** @var ResultPhoto $photo */
        $photo = ResultPhoto::query()->findOrFail($photoId);
        $this->assertTrue($photo->is_camera_capture);
        $this->assertEquals(Carbon::now(), $photo->captured_at);

        // Stored strictly on private storage, not public
        Storage::disk('private')->assertExists($photo->storage_path);
        Storage::disk('public')->assertMissing($photo->storage_path);
    }

    public function test_re_s_t04_rejects_non_camera_capture_with_422_no_t_camer_a_capture(): void
    {
        $inspector = $this->createVerifiedInspector();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $file = UploadedFile::fake()->image('gallery.jpg');

        $response = $this->actingAs($inspector)->postJson(
            route('result.photos.upload', ['job' => $job->id]),
            [
                'file' => $file,
                'template_item_id' => $templateItem->id,
                'is_camera_capture' => false,
            ]
        );

        $response->assertStatus(422);
        $response->assertJson([
            'data' => null,
            'error' => [
                'code' => 'NOT_CAMERA_CAPTURE',
            ],
        ]);
    }

    public function test_re_s_t05_rejects_file_exceeding_2_mb(): void
    {
        $inspector = $this->createVerifiedInspector();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $largeFile = UploadedFile::fake()->create('heavy.jpg', 2049, 'image/jpeg');

        $response = $this->actingAs($inspector)->postJson(
            route('result.photos.upload', ['job' => $job->id]),
            [
                'file' => $largeFile,
                'template_item_id' => $templateItem->id,
                'is_camera_capture' => true,
            ]
        );

        $response->assertStatus(413);
        $response->assertJson([
            'data' => null,
            'error' => [
                'code' => 'FILE_TOO_LARGE',
            ],
        ]);
    }

    public function test_re_s_t05_rejects_non_image_file(): void
    {
        $inspector = $this->createVerifiedInspector();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $textDoc = UploadedFile::fake()->create('notes.txt', 50, 'text/plain');

        $response = $this->actingAs($inspector)->postJson(
            route('result.photos.upload', ['job' => $job->id]),
            [
                'file' => $textDoc,
                'template_item_id' => $templateItem->id,
                'is_camera_capture' => true,
            ]
        );

        $response->assertStatus(415);
        $response->assertJson([
            'data' => null,
            'error' => [
                'code' => 'UNSUPPORTED_TYPE',
            ],
        ]);
    }

    public function test_rejects_unauthenticated_and_unauthorized_roles(): void
    {
        $inspector = $this->createVerifiedInspector();
        $otherInspector = $this->createVerifiedInspector();
        $user = User::factory()->user()->create();
        $admin = User::factory()->admin()->create();

        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $file = UploadedFile::fake()->image('test.jpg');
        $payload = [
            'file' => $file,
            'template_item_id' => $templateItem->id,
            'is_camera_capture' => true,
        ];

        // Guest -> 401
        $this->postJson(route('result.photos.upload', ['job' => $job->id]), $payload)
            ->assertUnauthorized();

        // Regular User -> 403
        $this->actingAs($user)
            ->postJson(route('result.photos.upload', ['job' => $job->id]), $payload)
            ->assertForbidden();

        // Admin -> 403 (upload is inspector only)
        $this->actingAs($admin)
            ->postJson(route('result.photos.upload', ['job' => $job->id]), $payload)
            ->assertForbidden();

        // Other Inspector -> 403
        $this->actingAs($otherInspector)
            ->postJson(route('result.photos.upload', ['job' => $job->id]), $payload)
            ->assertForbidden();
    }

    public function test_deletes_photo_via_delete_endpoint(): void
    {
        $inspector = $this->createVerifiedInspector();
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

        $path = "results/{$result->id}/photos/delete_me.jpg";
        Storage::disk('private')->put($path, 'sample bytes');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $response = $this->actingAs($inspector)->deleteJson(
            route('result.photos.delete', ['job' => $job->id, 'photo' => $photo->id])
        );

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'deleted' => true,
            ],
            'error' => null,
        ]);

        Storage::disk('private')->assertMissing($path);
        $this->assertDatabaseMissing('result_photos', ['id' => $photo->id]);
    }
}
