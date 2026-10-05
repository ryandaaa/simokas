<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Listeners;

use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobPaid;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Events\PaymentSucceeded;
use Illuminate\Support\Facades\DB;

/**
 * Reaksi pembayaran inspeksi: job requested menjadi paid.
 *
 * Idempoten: pembaruan berkondisi (where status = requested) membuat event
 * ganda tidak berefek ganda. Pembayaran gagal atau tertunda tidak
 * memancarkan PaymentSucceeded sehingga tidak mengubah job (PAY-R10).
 */
final class MarkJobPaidOnPaymentSucceeded
{
    public function handle(PaymentSucceeded $event): void
    {
        if ($event->purpose !== PaymentPurpose::Inspection) {
            return;
        }

        $now = now();

        $affected = DB::transaction(function () use ($event, $now): int {
            return InspectionJob::query()
                ->whereKey($event->refId)
                ->where('status', JobStatus::Requested->value)
                ->update([
                    'status' => JobStatus::Paid->value,
                    'available_since' => $now,
                    'updated_at' => $now,
                ]);
        });

        if ($affected === 0) {
            return;
        }

        /** @var InspectionJob $job */
        $job = InspectionJob::query()->findOrFail($event->refId);

        JobPaid::dispatch(
            jobId: $job->getKey(),
            listingId: $job->listing_id,
            requesterId: $job->requester_id,
        );
    }
}
