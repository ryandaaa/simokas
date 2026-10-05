<?php

declare(strict_types=1);

namespace App\Domains\Result\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Exceptions\JobNotInProgressException;
use App\Domains\Result\Exceptions\TemplateNotFoundException;
use App\Domains\Result\Exceptions\UnauthorizedInspectorException;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use Illuminate\Support\Facades\DB;

final class GetOrCreateDraftResult
{
    public function handle(User $inspector, InspectionJob $job): InspectionResult
    {
        if ($job->inspector_id === null || (string) $inspector->getKey() !== (string) $job->inspector_id) {
            throw UnauthorizedInspectorException::create();
        }

        if ($job->status !== JobStatus::InProgress) {
            throw JobNotInProgressException::create();
        }

        return DB::transaction(function () use ($inspector, $job): InspectionResult {
            /** @var InspectionResult|null $existingResult */
            $existingResult = InspectionResult::query()
                ->where('job_id', $job->getKey())
                ->first();

            if ($existingResult !== null) {
                return $existingResult->load(['template.items', 'items']);
            }

            $vehicleType = $job->listing->vehicle_type->value;

            /** @var InspectionTemplate|null $template */
            $template = InspectionTemplate::query()
                ->where('vehicle_type', $vehicleType)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();

            if ($template === null) {
                throw TemplateNotFoundException::create($vehicleType);
            }

            /** @var InspectionResult $result */
            $result = InspectionResult::query()->create([
                'job_id' => $job->getKey(),
                'listing_id' => $job->listing_id,
                'inspector_id' => $inspector->getKey(),
                'template_id' => $template->getKey(),
                'status' => ResultStatus::Draft,
            ]);

            return $result->load(['template.items', 'items']);
        });
    }
}
