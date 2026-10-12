<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class ViewResultPhotoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');
    }

    public function test_res_t16_signed_url_expires_and_fails_after_expiration(): void
    {
        Carbon::setTestNow('2026-10-06 10:00:00');

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

        $path = "results/{$result->id}/photos/pic.jpg";
        Storage::disk('private')->put($path, 'fake private image');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        // Generate temporary URL valid for 10 minutes (expires at 10:10:00)
        $signedUrl = $photo->temporaryUrl(10);

        // Before expiration -> 200 OK for inspector
        $this->actingAs($inspector)->get($signedUrl)->assertOk();

        // Travel 11 minutes -> now 10:11:00 (expired)
        Carbon::setTestNow('2026-10-06 10:11:00');

        $this->actingAs($inspector)->get($signedUrl)->assertForbidden();
    }

    public function test_res_t16_signed_url_cannot_be_accessed_by_unauthorized_parties(): void
    {
        Carbon::setTestNow('2026-10-06 10:00:00');

        $assignedInspector = User::factory()->inspector()->create();
        $otherInspector = User::factory()->inspector()->create();
        $otherUser = User::factory()->user()->create();

        $job = InspectionJob::factory()->inProgress($assignedInspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $assignedInspector->id,
            'template_id' => $template->id,
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
        ]);

        $path = "results/{$result->id}/photos/secure.jpg";
        Storage::disk('private')->put($path, 'classified image');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $signedUrl = $photo->temporaryUrl(30);

        // Assigned Inspector -> 200 OK
        $this->actingAs($assignedInspector)->get($signedUrl)->assertOk();

        // Other Inspector -> 403 Forbidden
        $this->actingAs($otherInspector)->get($signedUrl)->assertForbidden();

        // Other regular user -> 403 Forbidden
        $this->actingAs($otherUser)->get($signedUrl)->assertForbidden();

        // Guest -> 403 Forbidden
        $this->get($signedUrl)->assertForbidden();
    }

    public function test_serves_photo_with_correct_headers_and_private_storage(): void
    {
        Carbon::setTestNow('2026-10-06 10:00:00');

        $inspector = User::factory()->inspector()->create();
        $admin = User::factory()->admin()->create();

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

        $path = "results/{$result->id}/photos/sample.jpg";
        Storage::disk('private')->put($path, 'image binary data');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $signedUrl = $photo->temporaryUrl(30);

        // Photo exists in private storage and never in public
        Storage::disk('private')->assertExists($path);
        Storage::disk('public')->assertMissing($path);

        $response = $this->actingAs($inspector)->get($signedUrl);
        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');

        // Admin can also view
        $adminResponse = $this->actingAs($admin)->get($signedUrl);
        $adminResponse->assertOk();
    }

    public function test_allows_listing_owner_and_requester_to_view_photos_when_submitted(): void
    {
        Carbon::setTestNow('2026-10-06 10:00:00');

        $inspector = User::factory()->inspector()->create();
        $seller = User::factory()->user()->create();
        $requester = User::factory()->user()->create();
        $stranger = User::factory()->user()->create();

        $job = InspectionJob::factory()->inProgress($inspector)->create([
            'requester_id' => $requester->id,
        ]);
        $job->listing->update(['seller_id' => $seller->id]);

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

        $path = "results/{$result->id}/photos/submitted.jpg";
        Storage::disk('private')->put($path, 'submitted image');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $signedUrl = $photo->temporaryUrl(30);

        // Seller -> 200 OK
        $this->actingAs($seller)->get($signedUrl)->assertOk();

        // Requester -> 200 OK
        $this->actingAs($requester)->get($signedUrl)->assertOk();

        // Stranger -> 403 Forbidden
        $this->actingAs($stranger)->get($signedUrl)->assertForbidden();
    }
}
