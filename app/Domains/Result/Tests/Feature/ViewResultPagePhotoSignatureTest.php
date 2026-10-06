<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Feature;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ViewResultPagePhotoSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');
    }

    #[Test]
    public function result_page_for_authorized_user_contains_signed_photo_urls_that_can_be_opened(): void
    {
        $seller = User::factory()->user()->create(['status' => UserStatus::Active]);
        $inspector = User::factory()->inspector()->create(['status' => UserStatus::Active]);
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $job->listing->update(['seller_id' => $seller->id]);

        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'label' => 'Kondisi Mesin',
        ]);

        $result = InspectionResult::factory()->submitted()->create([
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

        $path = "results/{$result->id}/photos/engine-photo.jpg";
        Storage::disk('private')->put($path, 'real photo content');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $response = $this->actingAs($seller)->get(route('results.show', ['listing' => $job->listing_id]));
        $response->assertOk();

        $content = $response->getContent();

        // Must find img src containing photo URL
        preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches);
        $this->assertNotEmpty($matches, 'No img src found on authorized result page');

        $photoUrl = html_entity_decode($matches[1]);

        // URL must contain signature query parameter
        $this->assertStringContainsString('signature=', $photoUrl);

        // Fetching the signed URL should return 200 OK
        $photoResponse = $this->actingAs($seller)->get($photoUrl);
        $photoResponse->assertOk();

        // Modifying URL (removing signature) must return 403 Forbidden
        $parsedUrl = parse_url($photoUrl);
        $tamperedUrl = $parsedUrl['path'];
        $tamperedResponse = $this->actingAs($seller)->get($tamperedUrl);
        $tamperedResponse->assertForbidden();
    }

    #[Test]
    public function locked_summary_page_for_unauthorized_user_does_not_contain_photo_urls(): void
    {
        $seller = User::factory()->user()->create(['status' => UserStatus::Active]);
        $inspector = User::factory()->inspector()->create(['status' => UserStatus::Active]);
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $job->listing->update(['seller_id' => $seller->id]);

        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'label' => 'Kondisi Mesin',
        ]);

        $result = InspectionResult::factory()->submitted()->create([
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

        $path = "results/{$result->id}/photos/engine-photo.jpg";
        Storage::disk('private')->put($path, 'real photo content');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $stranger = User::factory()->user()->create(['status' => UserStatus::Active]);

        $response = $this->actingAs($stranger)->get(route('results.show', ['listing' => $job->listing_id]));
        $response->assertOk();

        $content = $response->getContent();

        // Locked summary should not contain photo id or photo endpoint or signature
        $this->assertStringNotContainsString($photo->id, $content);
        $this->assertStringNotContainsString('results/' . $result->id . '/photos', $content);
        $this->assertStringNotContainsString('signature=', $content);
    }

    #[Test]
    public function json_endpoint_for_authorized_user_provides_signed_photo_urls(): void
    {
        $seller = User::factory()->user()->create(['status' => UserStatus::Active]);
        $inspector = User::factory()->inspector()->create(['status' => UserStatus::Active]);
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $job->listing->update(['seller_id' => $seller->id]);

        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'label' => 'Kondisi Mesin',
        ]);

        $result = InspectionResult::factory()->submitted()->create([
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

        $path = "results/{$result->id}/photos/json-engine.jpg";
        Storage::disk('private')->put($path, 'json photo binary');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $response = $this->actingAs($seller)->getJson(route('results.json', ['result' => $result->id]));
        $response->assertOk();

        $photoUrl = $response->json('data.photos.0.url') ?? $response->json('photos.0.url');
        $this->assertNotNull($photoUrl);
        $this->assertStringContainsString('signature=', $photoUrl);

        $photoResponse = $this->actingAs($seller)->get($photoUrl);
        $photoResponse->assertOk();
    }
}
