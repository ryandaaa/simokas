<?php

declare(strict_types=1);

namespace App\Domains\Result\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Contracts\InspectionJobResultContract;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Events\ResultReopened;
use App\Domains\Result\Exceptions\ReopenReasonRequiredException;
use App\Domains\Result\Models\InspectionResult;
use App\Support\Clock;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

final class ReopenInspectionResult
{
    public function __construct(
        private readonly InspectionJobResultContract $jobResultContract,
        private readonly Dispatcher $events,
    ) {}

    public function handle(User $admin, InspectionResult $result, string $reason): InspectionResult
    {
        // 1. Authorization: admin only (RES-R4, RES-E14)
        if (! $admin->isAdmin()) {
            throw new DomainException(
                __('inspection.errors.not_authorized'),
                'NOT_ALLOWED',
                403,
            );
        }

        // 2. Validation: reason is required for audit
        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw ReopenReasonRequiredException::create();
        }

        return DB::transaction(function () use ($admin, $result, $trimmedReason): InspectionResult {
            /** @var InspectionResult $lockedResult */
            $lockedResult = InspectionResult::query()
                ->whereKey($result->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Idempotency: if already draft, return immediately
            if ($lockedResult->status === ResultStatus::Draft) {
                return $lockedResult->load(['items', 'photos']);
            }

            // Update result back to draft and record audit fields
            $now = Clock::now();
            $lockedResult->update([
                'status' => ResultStatus::Draft,
                'reopened_at' => $now,
                'reopened_by' => $admin->getKey(),
                'reopen_reason' => $trimmedReason,
            ]);

            // Transition job back to in_progress via Inspection contract
            $this->jobResultContract->reopenJob($lockedResult->job_id);

            // Dispatch ResultReopened event
            $this->events->dispatch(new ResultReopened(
                resultId: (string) $lockedResult->id,
                jobId: (string) $lockedResult->job_id,
                inspectorId: (string) $lockedResult->inspector_id,
                reopenedBy: (string) $admin->getKey(),
                reason: $trimmedReason,
            ));

            return $lockedResult->refresh()->load(['items', 'photos']);
        });
    }
}
