<?php

declare(strict_types=1);

namespace App\Domains\Result\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Exceptions\InvalidRatingException;
use App\Domains\Result\Exceptions\JobNotInProgressException;
use App\Domains\Result\Exceptions\ResultAlreadySubmittedException;
use App\Domains\Result\Exceptions\UnauthorizedInspectorException;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultItem;
use App\Support\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

final class SaveDraftResult
{
    /**
     * @param  array<string|int, mixed>  $itemsData
     */
    public function handle(
        User $inspector,
        InspectionResult $result,
        array $itemsData,
        ?string $summaryNote = null
    ): InspectionResult {
        if ((string) $inspector->getKey() !== (string) $result->inspector_id) {
            throw UnauthorizedInspectorException::create();
        }

        if ($result->status !== ResultStatus::Draft) {
            throw ResultAlreadySubmittedException::create();
        }

        if ($result->job->status !== JobStatus::InProgress) {
            throw JobNotInProgressException::create();
        }

        if ($summaryNote !== null && mb_strlen($summaryNote) > 2000) {
            throw new DomainException(
                __('inspection.form.summary_note_too_long'),
                'SUMMARY_NOTE_TOO_LONG',
                422,
            );
        }

        return DB::transaction(function () use ($result, $itemsData, $summaryNote): InspectionResult {
            if ($summaryNote !== null) {
                $result->summary_note = $summaryNote;
                $result->save();
            }

            foreach ($itemsData as $key => $data) {
                if (! is_array($data)) {
                    continue;
                }

                $templateItemId = isset($data['template_item_id'])
                    ? (string) $data['template_item_id']
                    : (string) $key;

                $rating = isset($data['rating']) && is_string($data['rating']) && $data['rating'] !== ''
                    ? $data['rating']
                    : null;

                if ($rating !== null && ItemRating::tryFrom($rating) === null) {
                    throw InvalidRatingException::create($rating);
                }

                $note = isset($data['note']) && is_string($data['note']) && $data['note'] !== ''
                    ? $data['note']
                    : null;

                if ($note !== null && mb_strlen($note) > 1000) {
                    throw new DomainException(
                        __('inspection.form.note_too_long'),
                        'NOTE_TOO_LONG',
                        422,
                    );
                }

                ResultItem::query()->updateOrCreate(
                    [
                        'result_id' => $result->getKey(),
                        'template_item_id' => $templateItemId,
                    ],
                    [
                        'rating' => $rating,
                        'note' => $note,
                    ]
                );
            }

            $result->touch();

            return $result->refresh()->load('items');
        });
    }
}
