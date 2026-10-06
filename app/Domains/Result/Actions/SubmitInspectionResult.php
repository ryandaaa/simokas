<?php

declare(strict_types=1);

namespace App\Domains\Result\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Contracts\InspectionJobResultContract;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Events\ResultSubmitted;
use App\Domains\Result\Exceptions\JobNotInProgressException;
use App\Domains\Result\Exceptions\MinPhotosNotMetException;
use App\Domains\Result\Exceptions\RequiredItemsMissingException;
use App\Domains\Result\Exceptions\UnauthorizedInspectorException;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\TemplateItem;
use App\Domains\Result\Services\ScoreCalculator;
use App\Support\Clock;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

final class SubmitInspectionResult
{
    public function __construct(
        private readonly ScoreCalculator $scoreCalculator,
        private readonly InspectionJobResultContract $jobResultContract,
        private readonly Dispatcher $events,
    ) {}

    public function handle(User $inspector, InspectionResult $result): InspectionResult
    {
        // 1. Authorization check: only assigned inspector (RES-R2)
        if ((string) $inspector->getKey() !== (string) $result->inspector_id) {
            throw UnauthorizedInspectorException::create();
        }

        return DB::transaction(function () use ($result): InspectionResult {
            // Lock the result row for concurrency protection
            /** @var InspectionResult $lockedResult */
            $lockedResult = InspectionResult::query()
                ->whereKey($result->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency: second submit returns already submitted result without duplicate effect
            if ($lockedResult->status === ResultStatus::Submitted) {
                return $lockedResult->load(['items', 'photos']);
            }

            // Job status check: must be in_progress
            if ($lockedResult->job->status !== JobStatus::InProgress) {
                throw JobNotInProgressException::create();
            }

            // 2. Load template items and existing result items with photos
            /** @var \Illuminate\Database\Eloquent\Collection<int, TemplateItem> $templateItems */
            $templateItems = TemplateItem::query()
                ->where('template_id', $lockedResult->template_id)
                ->orderBy('sort_order')
                ->get();

            /** @var \Illuminate\Database\Eloquent\Collection<int, ResultItem> $resultItems */
            $resultItems = ResultItem::query()
                ->where('result_id', $lockedResult->getKey())
                ->with('photos')
                ->get()
                ->keyBy('template_item_id');

            // 3. Validation: all required items must have ratings (RES-R3, RES-T03)
            $missingRequired = [];
            foreach ($templateItems as $templateItem) {
                if ($templateItem->is_required) {
                    $item = $resultItems->get($templateItem->id);
                    if ($item === null || $item->rating === null || $item->rating->value === '') {
                        $missingRequired[] = [
                            'id' => (string) $templateItem->id,
                            'label' => $templateItem->label,
                        ];
                    }
                }
            }

            if (! empty($missingRequired)) {
                throw RequiredItemsMissingException::withItems($missingRequired);
            }

            // 4. Validation: minimum photos must be met for items requiring photos (RES-R3, RES-T03)
            $missingPhotos = [];
            foreach ($templateItems as $templateItem) {
                if ($templateItem->requires_photo) {
                    $item = $resultItems->get($templateItem->id);
                    $photoCount = $item !== null ? $item->photos->count() : 0;
                    if ($photoCount < 1) {
                        $missingPhotos[] = [
                            'id' => (string) $templateItem->id,
                            'label' => $templateItem->label,
                        ];
                    }
                }
            }

            if (! empty($missingPhotos)) {
                throw MinPhotosNotMetException::withItems($missingPhotos);
            }

            // 5. Calculate total score and recommendation via pure ScoreCalculator (RES-T06, RES-T07)
            $calcItems = [];
            foreach ($templateItems as $templateItem) {
                $item = $resultItems->get($templateItem->id);
                $calcItems[] = [
                    'weight' => $templateItem->weight,
                    'rating' => $item?->rating,
                    'is_critical' => $templateItem->is_critical,
                ];
            }

            $scoreResult = $this->scoreCalculator->calculate($calcItems);

            // 6. Lock report: update result to submitted
            $now = Clock::now();
            $lockedResult->update([
                'status' => ResultStatus::Submitted,
                'total_score' => $scoreResult->totalScore,
                'recommendation' => $scoreResult->recommendation->value,
                'submitted_at' => $now,
            ]);

            // 7. Transition job to result_submitted via Inspection contract
            $this->jobResultContract->markResultSubmitted($lockedResult->job_id);

            // 8. Dispatch ResultSubmitted event exactly once with primitive IDs
            $this->events->dispatch(new ResultSubmitted(
                resultId: (string) $lockedResult->id,
                jobId: (string) $lockedResult->job_id,
                inspectorId: (string) $lockedResult->inspector_id,
                listingId: (string) $lockedResult->listing_id,
            ));

            return $lockedResult->refresh()->load(['items', 'photos']);
        });
    }
}
