<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Setting\Contracts\SettingsReader;
use App\Support\Clock;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Action terjadwal untuk membatalkan job 'paid' yang tidak diambil dalam batas waktu timeout (D-01, JOB-R8, JOB-T10).
 *
 * Dibatalkan otomatis, refund 100% via Ledger, dan listing kembali active via JobCancelled event.
 * Dijalankan harian oleh scheduler.
 * Idempoten: job yang sudah dibatalkan tidak diproses ulang.
 */
final class CancelUnclaimedPaidJobs
{
    public function __construct(
        private readonly SettingsReader $settings,
        private readonly Ledger $ledger,
        private readonly Dispatcher $events,
    ) {}

    public function handle(): int
    {
        $timeoutDays = $this->settings->getPaidJobTimeoutDays();
        $now = Clock::now();
        $cutoff = $now->copy()->subDays($timeoutDays);
        $cancelledCount = 0;

        /** @var \Illuminate\Database\Eloquent\Collection<int, InspectionJob> $jobs */
        $jobs = InspectionJob::query()
            ->where('status', JobStatus::Paid->value)
            ->whereNotNull('available_since')
            ->where('available_since', '<=', $cutoff)
            ->get();

        foreach ($jobs as $job) {
            $cancelled = DB::transaction(function () use ($job, $timeoutDays, $now, $cutoff): bool {
                $affected = InspectionJob::query()
                    ->whereKey($job->getKey())
                    ->where('status', JobStatus::Paid->value)
                    ->where('available_since', '<=', $cutoff)
                    ->update([
                        'status' => JobStatus::Cancelled->value,
                        'cancel_reason' => "Dibatalkan otomatis oleh sistem (tidak diambil dalam {$timeoutDays} hari)",
                        'cancelled_by' => null,
                        'updated_at' => $now,
                    ]);

                if ($affected === 0) {
                    return false;
                }

                // Refund 100% via Ledger (idempoten)
                $payment = Payment::query()
                    ->where('purpose', PaymentPurpose::Inspection)
                    ->where('ref_id', $job->getKey())
                    ->whereIn('status', [PaymentStatus::Success, PaymentStatus::Refunded])
                    ->first();

                if ($payment !== null) {
                    $this->ledger->refundPayment($payment->id);
                }

                $this->events->dispatch(new JobCancelled(
                    jobId: (string) $job->getKey(),
                    listingId: (string) $job->listing_id,
                    cancelledBy: null,
                    cancelReason: "Dibatalkan otomatis oleh sistem (tidak diambil dalam {$timeoutDays} hari)",
                ));

                return true;
            });

            if ($cancelled) {
                $cancelledCount++;
            }
        }

        return $cancelledCount;
    }
}
