<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Enums\InspectionRecommendation;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InspectionResultModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_result_and_items(): void
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
            'summary_note' => 'Catatan awal.',
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
            'rating' => ItemRating::Good,
            'note' => 'Kondisi bagus',
        ]);

        $this->assertSame(ResultStatus::Draft, $result->status);
        $this->assertSame('Catatan awal.', $result->summary_note);
        $this->assertCount(1, $result->items);
        $this->assertSame(ItemRating::Good, $resultItem->rating);
        $this->assertSame('Kondisi bagus', $resultItem->note);
        $this->assertTrue($result->job->is($job));
        $this->assertTrue($result->template->is($template));
    }

    public function test_it_casts_submitted_result_attributes(): void
    {
        $admin = User::factory()->admin()->create();
        $result = InspectionResult::factory()->submitted()->create([
            'total_score' => 82.50,
            'reopened_at' => now(),
            'reopened_by' => $admin->id,
            'reopen_reason' => 'Perlu foto tambahan pada bodi.',
        ]);

        $this->assertSame(ResultStatus::Submitted, $result->status);
        $this->assertSame('82.50', (string) $result->total_score);
        $this->assertSame(InspectionRecommendation::Recommended, $result->recommendation);
        $this->assertNotNull($result->submitted_at);
        $this->assertNotNull($result->reopened_at);
        $this->assertSame($admin->id, $result->reopened_by);
        $this->assertSame('Perlu foto tambahan pada bodi.', $result->reopen_reason);
        $this->assertTrue($result->reopenedByUser->is($admin));
    }
}
