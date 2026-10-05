<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Support\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Action pembatalan job oleh inspektor (JOB-3, JOB-T07, JOB-E3, JOB-E16).
 *
 * Status taken atau scheduled kembali menjadi paid.
 * inspector_id dikosongkan, scheduled_at dikosongkan.
 * available_since dihitung ulang dari saat kembali paid.
 * Alasan wajib dicatat untuk tinjauan admin.
 * Listing tetap in_inspection dan dana tetap ditahan (tanpa refund).
 */
final class CancelJobByInspector
{
    public function handle(User $inspector, InspectionJob $job, string $reason): InspectionJob
    {
        $trimmedReason = trim($reason);

        if ($trimmedReason === '') {
            throw new DomainException(
                __('inspection.errors.cancel_reason_required'),
                'CANCEL_REASON_REQUIRED',
                422,
            );
        }

        if ($job->inspector_id === null || (string) $job->inspector_id !== (string) $inspector->getKey()) {
            throw InvalidJobStatusTransitionException::alreadyProcessed($job->status);
        }

        if (! in_array($job->status, [JobStatus::Taken, JobStatus::Scheduled], true)) {
            throw InvalidJobStatusTransitionException::fromStatus($job->status, JobStatus::Paid);
        }

        return DB::transaction(function () use ($inspector, $job, $trimmedReason): InspectionJob {
            $now = now();

            $affected = InspectionJob::query()
                ->whereKey($job->getKey())
                ->where('inspector_id', $inspector->getKey())
                ->whereIn('status', [JobStatus::Taken->value, JobStatus::Scheduled->value])
                ->update([
                    'status' => JobStatus::Paid->value,
                    'inspector_id' => null,
                    'scheduled_at' => null,
                    'available_since' => $now,
                    'cancel_reason' => $trimmedReason,
                    'cancelled_by' => $inspector->getKey(),
                    'updated_at' => $now,
                ]);

            if ($affected === 0) {
                /** @var InspectionJob $fresh */
                $fresh = InspectionJob::query()->findOrFail($job->getKey());

                throw InvalidJobStatusTransitionException::alreadyProcessed($fresh->status);
            }

            return $job->refresh();
        });
    }
}
