<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Exceptions\CannotCancelInProgressJobException;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Action pembatalan job oleh pemohon (JOB-3, JOB-T13, JOB-T14, JOB-E11, JOB-E17).
 *
 * Status requested menjadi cancelled tanpa refund (belum bayar).
 * Status paid, taken, scheduled menjadi cancelled dengan REFUND 100% via Ledger.
 * Status in_progress DITOLAK untuk pemohon (hanya admin yang boleh, JOB-E17).
 * Seluruh operasi status dan refund berada dalam satu transaksi DB yang sama.
 * Memancarkan event JobCancelled untuk memicu kembalinya listing ke active.
 */
final class CancelJobByRequester
{
    public function __construct(
        private readonly Ledger $ledger,
        private readonly Dispatcher $events,
    ) {}

    public function handle(User $requester, InspectionJob $job, ?string $reason = null): InspectionJob
    {
        // Pastikan aktor adalah pemohon yang mengajukan job
        if ((string) $job->requester_id !== (string) $requester->getKey()) {
            throw InvalidJobStatusTransitionException::alreadyProcessed($job->status);
        }

        // Jika sudah cancelled, kembalikan secara idempoten
        if ($job->status === JobStatus::Cancelled) {
            return $job->fresh() ?? $job;
        }

        // JOB-E17 / JOB-T14: Pemohon hendak membatalkan saat in_progress ditolak
        if ($job->status === JobStatus::InProgress) {
            throw CannotCancelInProgressJobException::create();
        }

        if (! in_array($job->status, [
            JobStatus::Requested,
            JobStatus::Paid,
            JobStatus::Taken,
            JobStatus::Scheduled,
        ], true)) {
            throw InvalidJobStatusTransitionException::fromStatus($job->status, JobStatus::Cancelled);
        }

        $oldStatus = $job->status;

        return DB::transaction(function () use ($requester, $job, $oldStatus, $reason): InspectionJob {
            $now = now();

            $affected = InspectionJob::query()
                ->whereKey($job->getKey())
                ->where('status', $oldStatus->value)
                ->update([
                    'status' => JobStatus::Cancelled->value,
                    'cancel_reason' => $reason,
                    'cancelled_by' => $requester->getKey(),
                    'updated_at' => $now,
                ]);

            if ($affected === 0) {
                /** @var InspectionJob $fresh */
                $fresh = InspectionJob::query()->findOrFail($job->getKey());

                if ($fresh->status === JobStatus::Cancelled) {
                    return $fresh;
                }

                // JOB-E8: Persaingan menghasilkan satu pemenang dan satu konflik 409
                throw InvalidJobStatusTransitionException::alreadyProcessed($fresh->status);
            }

            // Refund 100% untuk status yang sudah dibayar: paid, taken, scheduled
            if (in_array($oldStatus, [JobStatus::Paid, JobStatus::Taken, JobStatus::Scheduled], true)) {
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
                cancelledBy: (string) $requester->getKey(),
                cancelReason: $reason,
            ));

            return $freshJob;
        });
    }
}
