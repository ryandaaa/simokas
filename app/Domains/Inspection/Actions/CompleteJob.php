<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Inspection\Exceptions\InvalidJobStatusTransitionException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Support\Clock;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Action penyelesaian job inspeksi (D-03, JOB-R9, JOB-T11, JOB-T15, JOB-4).
 *
 * Mengubah status result_submitted menjadi completed, membayarkan fee inspeksi ke ledger,
 * dan memancarkan event JobCompleted.
 * Bersifat idempoten.
 */
final class CompleteJob
{
    public function __construct(
        private readonly Ledger $ledger,
        private readonly Dispatcher $events,
    ) {}

    public function handle(InspectionJob $job): InspectionJob
    {
        if ($job->status === JobStatus::Completed) {
            return $job->fresh() ?? $job;
        }

        if ($job->status !== JobStatus::ResultSubmitted) {
            throw InvalidJobStatusTransitionException::fromStatus($job->status, JobStatus::Completed);
        }

        return DB::transaction(function () use ($job): InspectionJob {
            $now = Clock::now();

            $affected = InspectionJob::query()
                ->whereKey($job->getKey())
                ->where('status', JobStatus::ResultSubmitted->value)
                ->update([
                    'status' => JobStatus::Completed->value,
                    'completed_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($affected === 0) {
                /** @var InspectionJob $fresh */
                $fresh = InspectionJob::query()->findOrFail($job->getKey());

                if ($fresh->status === JobStatus::Completed) {
                    return $fresh;
                }

                throw InvalidJobStatusTransitionException::alreadyProcessed($fresh->status);
            }

            // Posting fee inspektor dan komisi platform ke Ledger bila belum diposting (JOB-R6, JOB-E13, JOB-E15)
            $payment = Payment::query()
                ->where('purpose', PaymentPurpose::Inspection)
                ->where('ref_id', $job->getKey())
                ->where('status', PaymentStatus::Success)
                ->first();

            if ($payment !== null && $job->inspector_id !== null) {
                $alreadyPosted = LedgerEntry::query()
                    ->where('payment_id', $payment->id)
                    ->where('entry_type', LedgerEntryType::InspectionFee)
                    ->exists();

                if (! $alreadyPosted) {
                    $this->ledger->postInspectionPayout(
                        paymentId: $payment->id,
                        inspectorId: (string) $job->inspector_id,
                        platformFeePct: (int) $job->platform_fee_pct,
                    );
                }
            }

            $freshJob = $job->refresh();

            $this->events->dispatch(new JobCompleted(
                jobId: (string) $freshJob->getKey(),
                listingId: (string) $freshJob->listing_id,
                inspectorId: $freshJob->inspector_id !== null ? (string) $freshJob->inspector_id : null,
                requesterId: (string) $freshJob->requester_id,
            ));

            return $freshJob;
        });
    }
}
