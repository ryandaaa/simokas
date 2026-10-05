<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

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
use Tests\TestCase;

final class ResultPhotoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_result_photo_and_relations(): void
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

        $capturedAt = Carbon::parse('2026-10-06 10:00:00');
        $stampedAt = Carbon::parse('2026-10-06 09:59:58');

        $photo = ResultPhoto::factory()->camera()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => 'results/photos/sample.jpg',
            'captured_at' => $capturedAt,
            'stamped_at' => $stampedAt,
        ]);

        $this->assertNotEmpty($photo->id);
        $this->assertTrue($photo->is_camera_capture);
        $this->assertSame('results/photos/sample.jpg', $photo->storage_path);
        $this->assertEquals($capturedAt, $photo->captured_at);
        $this->assertEquals($stampedAt, $photo->stamped_at);

        $this->assertTrue($photo->result->is($result));
        $this->assertTrue($photo->resultItem->is($resultItem));

        $this->assertCount(1, $result->refresh()->photos);
        $this->assertTrue($result->photos->first()->is($photo));

        $this->assertCount(1, $resultItem->refresh()->photos);
        $this->assertTrue($resultItem->photo->is($photo));

        // Test temporary url generation
        $url = $photo->temporaryUrl(15);
        $this->assertStringContainsString('results/'.$result->id.'/photos/'.$photo->id, $url);
        $this->assertStringContainsString('signature=', $url);
    }
}
