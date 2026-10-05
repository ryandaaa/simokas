<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Result\Actions\SaveDraftResult;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Exceptions\JobNotInProgressException;
use App\Domains\Result\Exceptions\ResultAlreadySubmittedException;
use App\Domains\Result\Exceptions\UnauthorizedInspectorException;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SaveDraftResultTest extends TestCase
{
    use RefreshDatabase;

    private SaveDraftResult $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new SaveDraftResult();
    }

    public function test_RES_T02_saves_draft_items_correctly(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = \App\Domains\Inspection\Models\InspectionJob::factory()->inProgress($inspector)->create();
        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);
        $templateItem = TemplateItem::factory()->create([
            'template_id' => $result->template_id,
            'is_required' => true,
        ]);

        $itemsData = [
            $templateItem->id => [
                'template_item_id' => $templateItem->id,
                'rating' => ItemRating::Good->value,
                'note' => 'Kondisi sangat baik',
            ],
        ];

        $updatedResult = $this->action->handle($inspector, $result, $itemsData, 'Catatan ringkasan');

        $this->assertEquals(ResultStatus::Draft, $updatedResult->status);

        $savedItem = ResultItem::query()
            ->where('result_id', $result->id)
            ->where('template_item_id', $templateItem->id)
            ->first();

        $this->assertNotNull($savedItem);
        $this->assertEquals(ItemRating::Good, $savedItem->rating);
        $this->assertEquals('Kondisi sangat baik', $savedItem->note);
        $this->assertEquals('Catatan ringkasan', $updatedResult->summary_note);
    }

    public function test_RES_T02_updates_existing_draft_item_on_resave(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = \App\Domains\Inspection\Models\InspectionJob::factory()->inProgress($inspector)->create();
        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);
        $templateItem = TemplateItem::factory()->create([
            'template_id' => $result->template_id,
        ]);

        // First save
        $this->action->handle($inspector, $result, [
            $templateItem->id => ['template_item_id' => $templateItem->id, 'rating' => 'good', 'note' => 'baik'],
        ]);

        // Second save with different rating
        $this->action->handle($inspector, $result->refresh(), [
            $templateItem->id => ['template_item_id' => $templateItem->id, 'rating' => 'fair', 'note' => 'cukup'],
        ]);

        // Should have only one ResultItem record, not duplicates
        $this->assertEquals(1, ResultItem::query()->where('result_id', $result->id)->count());
        $savedItem = ResultItem::query()->where('result_id', $result->id)->first();
        $this->assertEquals(ItemRating::Fair, $savedItem->rating);
    }

    public function test_RES_T14_throws_when_different_inspector_tries_to_save(): void
    {
        $inspector = User::factory()->inspector()->create();
        $otherInspector = User::factory()->inspector()->create();
        $job = \App\Domains\Inspection\Models\InspectionJob::factory()->inProgress($inspector)->create();
        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);

        $this->expectException(UnauthorizedInspectorException::class);

        $this->action->handle($otherInspector, $result, []);
    }

    public function test_throws_when_result_is_already_submitted(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = \App\Domains\Inspection\Models\InspectionJob::factory()->inProgress($inspector)->create();
        $result = InspectionResult::factory()->submitted()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);

        $this->expectException(ResultAlreadySubmittedException::class);

        $this->action->handle($inspector, $result, []);
    }

    public function test_throws_when_job_is_not_in_progress(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = \App\Domains\Inspection\Models\InspectionJob::factory()->inProgress($inspector)->create();
        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);

        // Change job status to something else
        $job->update(['status' => JobStatus::Completed]);

        $this->expectException(JobNotInProgressException::class);

        $this->action->handle($inspector, $result->refresh(), []);
    }

    public function test_save_with_empty_items_only_updates_summary_note(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = \App\Domains\Inspection\Models\InspectionJob::factory()->inProgress($inspector)->create();
        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);

        $updatedResult = $this->action->handle($inspector, $result, [], 'Catatan ringkasan tes');

        $this->assertEquals('Catatan ringkasan tes', $updatedResult->summary_note);
        $this->assertEquals(0, ResultItem::query()->where('result_id', $result->id)->count());
    }

    public function test_throws_when_item_rating_is_invalid(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = \App\Domains\Inspection\Models\InspectionJob::factory()->inProgress($inspector)->create();
        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);
        $templateItem = TemplateItem::factory()->create([
            'template_id' => $result->template_id,
        ]);

        $this->expectException(\App\Domains\Result\Exceptions\InvalidRatingException::class);

        $this->action->handle($inspector, $result, [
            $templateItem->id => [
                'template_item_id' => $templateItem->id,
                'rating' => 'invalid_rating',
            ],
        ]);
    }

    public function test_throws_when_item_note_is_too_long(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = \App\Domains\Inspection\Models\InspectionJob::factory()->inProgress($inspector)->create();
        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);
        $templateItem = TemplateItem::factory()->create([
            'template_id' => $result->template_id,
        ]);

        $this->expectException(\App\Support\Exceptions\DomainException::class);

        $this->action->handle($inspector, $result, [
            $templateItem->id => [
                'template_item_id' => $templateItem->id,
                'note' => str_repeat('a', 1001),
            ],
        ]);
    }

    public function test_throws_when_summary_note_is_too_long(): void
    {
        $inspector = User::factory()->inspector()->create();
        $job = \App\Domains\Inspection\Models\InspectionJob::factory()->inProgress($inspector)->create();
        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'inspector_id' => $inspector->id,
        ]);

        $this->expectException(\App\Support\Exceptions\DomainException::class);

        $this->action->handle($inspector, $result, [], str_repeat('a', 2001));
    }
}
