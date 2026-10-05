<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Action pembatalan job oleh admin (JOB-3, JOB-T14).
 *
 * Admin dapat membatalkan job pada status aktif apa pun, termasuk in_progress.
 * Untuk status paid, taken, scheduled: refund 100%.
 * Untuk in_progress: tidak ada refund secara default, admin dapat memilih lewat parameter $refund.
 * Untuk requested: tanpa refund (belum bayar).
 * Memancarkan event JobCancelled untuk memicu kembalinya listing ke active.
 */
final class CancelJobByAdmin
{
    public function __construct(
        private readonly Ledger $ledger,
        private readonly Dispatcher $events,
    ) {}

    public function handle(User $admin, InspectionJob $job, ?string $reason = null, bool $refund = false): InspectionJob
    {
        if (! $admin->isAdmin()) {
            throw InvalidJobStatusTransitionException::alreadyProcessed($job->status);
        }

        if ($job->status === JobStatus::Cancelled) {
            return $job->fresh() ?? $job;
        }

        if ($job->isFinal()) {
            throw InvalidJobStatusTransitionException::fromStatus($job->status, JobStatus::Cancelled);
        }

        $oldStatus = $job->status;

        return DB::transaction(function () use ($admin, $job, $oldStatus, $reason, $refund): InspectionJob {
            $now = now();

            $affected = InspectionJob::query()
                ->whereKey($job->getKey())
                ->where('status', $oldStatus->value)
                ->update([
                    'status' => JobStatus::Cancelled->value,
                    'cancel_reason' => $reason,
                    'cancelled_by' => $admin->getKey(),
                    'updated_at' => $now,
                ]);

            if ($affected === 0) {
                /** @var InspectionJob $fresh */
                $fresh = InspectionJob::query()->findOrFail($job->getKey());

                if ($fresh->status === JobStatus::Cancelled) {
                    return $fresh;
                }

                throw InvalidJobStatusTransitionException::alreadyProcessed($fresh->status);
            }

            // Tentukan apakah refund dilakukan
            $shouldRefund = match ($oldStatus) {
                JobStatus::Paid, JobStatus::Taken, JobStatus::Scheduled => true,
                JobStatus::InProgress, JobStatus::ResultSubmitted => $refund,
                default => false,
            };

            if ($shouldRefund) {
                $payment = Payment::query()
                    ->where('purpose', PaymentPurpose::Inspection)
                    ->where('ref_id', $job->getKey())
                    ->whereIn('status', [PaymentStatus::Success, PaymentStatus::Refunded])
                    ->first();

                if ($payment !== null) {
                    $this->ledger->refundPayment($payment->id);
                }
            }

            $freshJob = $job->refresh();

            $this->events->dispatch(new JobCancelled(
                jobId: (string) $freshJob->getKey(),
                listingId: (string) $freshJob->listing_id,
                cancelledBy: (string) $admin->getKey(),
                cancelReason: $reason,
            ));

            return $freshJob;
        });
    }
}
