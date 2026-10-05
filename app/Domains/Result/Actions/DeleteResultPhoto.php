<?php

declare(strict_types=1);

namespace App\Domains\Result\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Exceptions\JobNotInProgressException;
use App\Domains\Result\Exceptions\PhotoUploadException;
use App\Domains\Result\Exceptions\ResultAlreadySubmittedException;
use App\Domains\Result\Exceptions\UnauthorizedInspectorException;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultPhoto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class DeleteResultPhoto
{
    public function handle(
        User $inspector,
        InspectionJob|InspectionResult $target,
        ResultPhoto $photo,
    ): void {
        if ($target instanceof InspectionJob) {
            $job = $target;
            /** @var InspectionResult|null $result */
            $result = InspectionResult::query()->where('job_id', $job->getKey())->first();
        } else {
            $result = $target;
            $job = $result->job;
        }

        if ($result === null) {
            throw PhotoUploadException::notFound();
        }

        // Authorization & state checks
        if ((string) $inspector->getKey() !== (string) $job->inspector_id) {
            throw UnauthorizedInspectorException::create();
        }

        if ($job->status !== JobStatus::InProgress) {
            throw JobNotInProgressException::create();
        }

        if ($result->status !== ResultStatus::Draft) {
            throw ResultAlreadySubmittedException::create();
        }

        if ($photo->result_id !== $result->getKey()) {
            throw PhotoUploadException::notFound();
        }

        DB::transaction(function () use ($photo): void {
            Storage::disk('private')->delete($photo->storage_path);
            $photo->delete();
        });
    }
}
