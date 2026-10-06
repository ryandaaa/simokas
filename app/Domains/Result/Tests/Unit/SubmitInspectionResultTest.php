<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Contracts\InspectionJobResultContract;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Actions\SubmitInspectionResult;
use App\Domains\Result\Enums\InspectionRecommendation;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Events\ResultSubmitted;
use App\Domains\Result\Exceptions\JobNotInProgressException;
use App\Domains\Result\Exceptions\MinPhotosNotMetException;
use App\Domains\Result\Exceptions\RequiredItemsMissingException;
use App\Domains\Result\Exceptions\UnauthorizedInspectorException;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Models\TemplateItem;
use App\Domains\Result\Services\ScoreCalculator;
use App\Support\Clock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class SubmitInspectionResultTest extends TestCase
{
    use RefreshDatabase;

    private SubmitInspectionResult $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(SubmitInspectionResult::class);
    }

    public function test_res_t03_rejects_submission_when_required_items_missing_with_list(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();

        $item1 = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'label' => 'STNK',
            'is_required' => true,
            'requires_photo' => false,
        ]);
        $item2 = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'label' => 'BPKB',
            'is_required' => true,
            'requires_photo' => false,
        ]);

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        // Only item1 has a rating, item2 is missing
        ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $item1->id,
            'rating' => ItemRating::Good,
        ]);

        try {
            $this->action->handle($inspector, $result);
            $this->fail('Expected RequiredItemsMissingException was not thrown');
        } catch (RequiredItemsMissingException $e) {
            $this->assertSame('REQUIRED_ITEMS_MISSING', $e->getErrorCode());
            $this->assertSame(422, $e->getStatusCode());
            $missing = $e->getMissingItems();
            $this->assertCount(1, $missing);
            $this->assertSame((string) $item2->id, $missing[0]['id']);
            $this->assertSame('BPKB', $missing[0]['label']);
        }
    }

    public function test_res_t03_rejects_submission_when_min_photos_not_met_with_list(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();

        $item1 = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'label' => 'Bodi',
            'is_required' => true,
            'requires_photo' => true,
        ]);

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        // item1 is rated but has 0 photos
        ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $item1->id,
            'rating' => ItemRating::Good,
        ]);

        try {
            $this->action->handle($inspector, $result);
            $this->fail('Expected MinPhotosNotMetException was not thrown');
        } catch (MinPhotosNotMetException $e) {
            $this->assertSame('MIN_PHOTOS_NOT_MET', $e->getErrorCode());
            $this->assertSame(422, $e->getStatusCode());
            $missing = $e->getMissingPhotoItems();
            $this->assertCount(1, $missing);
            $this->assertSame((string) $item1->id, $missing[0]['id']);
            $this->assertSame('Bodi', $missing[0]['label']);
        }
    }

    public function test_res_t06_submits_and_calculates_score_correctly_with_dataset(): void
    {
        Event::fake([ResultSubmitted::class]);
        $action = app(SubmitInspectionResult::class);

        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();

        // 4 items with weight 1: Baik (2), Baik (2), Cukup (1), Buruk (0) => 5/8 * 100 = 62.5
        $items = [];
        for ($i = 1; $i <= 4; $i++) {
            $items[] = TemplateItem::factory()->create([
                'template_id' => $template->id,
                'weight' => 1,
                'is_required' => true,
                'requires_photo' => false,
                'is_critical' => false,
                'sort_order' => $i,
            ]);
        }

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        $ratings = [ItemRating::Good, ItemRating::Good, ItemRating::Fair, ItemRating::Poor];
        foreach ($items as $idx => $tItem) {
            ResultItem::factory()->create([
                'result_id' => $result->id,
                'template_item_id' => $tItem->id,
                'rating' => $ratings[$idx],
            ]);
        }

        $submitted = $action->handle($inspector, $result);

        $this->assertSame(ResultStatus::Submitted, $submitted->status);
        $this->assertSame('62.50', (string) $submitted->total_score);
        $this->assertSame(InspectionRecommendation::Caution, $submitted->recommendation);
        $this->assertNotNull($submitted->submitted_at);

        // Job is updated to result_submitted
        $this->assertSame(JobStatus::ResultSubmitted, $job->fresh()->status);
        $this->assertNotNull($job->fresh()->submitted_at);

        // Event dispatched exactly once
        Event::assertDispatched(ResultSubmitted::class, function (ResultSubmitted $event) use ($result, $job, $inspector) {
            return $event->resultId === (string) $result->id
                && $event->jobId === (string) $job->id
                && $event->inspectorId === (string) $inspector->id
                && $event->listingId === (string) $job->listing_id;
        });
    }

    public function test_res_t07_critical_item_rated_poor_forces_not_recommended(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();

        // 5 items: 4 Good (8/8), 1 Critical Poor (0/2) => 8/10 * 100 = 80.0
        // Score is 80.0 (normally recommended), but critical poor forces not_recommended!
        $normalItems = TemplateItem::factory()->count(4)->create([
            'template_id' => $template->id,
            'weight' => 1,
            'is_required' => true,
            'requires_photo' => false,
            'is_critical' => false,
        ]);

        $criticalItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'weight' => 1,
            'is_required' => true,
            'requires_photo' => false,
            'is_critical' => true,
        ]);

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        foreach ($normalItems as $item) {
            ResultItem::factory()->create([
                'result_id' => $result->id,
                'template_item_id' => $item->id,
                'rating' => ItemRating::Good,
            ]);
        }

        ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $criticalItem->id,
            'rating' => ItemRating::Poor,
        ]);

        $submitted = $this->action->handle($inspector, $result);

        $this->assertSame('80.00', (string) $submitted->total_score);
        $this->assertSame(InspectionRecommendation::NotRecommended, $submitted->recommendation);
    }

    public function test_res_t09_submitting_twice_has_single_effect_and_is_idempotent(): void
    {
        Event::fake([ResultSubmitted::class]);
        $action = app(SubmitInspectionResult::class);

        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $item = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'is_required' => true,
            'requires_photo' => false,
        ]);

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $item->id,
            'rating' => ItemRating::Good,
        ]);

        $first = $action->handle($inspector, $result);
        $second = $action->handle($inspector, $first);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(ResultStatus::Submitted, $second->status);
        $this->assertSame(JobStatus::ResultSubmitted, $job->fresh()->status);

        // Event dispatched exactly once
        Event::assertDispatchedTimes(ResultSubmitted::class, 1);
    }

    public function test_it_authorizes_only_the_assigned_inspector(): void
    {
        $assignedInspector = User::factory()->inspector()->create();
        $otherInspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->inProgress($assignedInspector)->create();
        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $assignedInspector->id,
        ]);

        $this->expectException(UnauthorizedInspectorException::class);
        $this->action->handle($otherInspector, $result);
    }

    public function test_it_rejects_submitting_when_job_is_not_in_progress(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = InspectionJob::factory()->scheduled($inspector)->create();
        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);

        $this->expectException(JobNotInProgressException::class);
        $this->action->handle($inspector, $result);
    }
}
