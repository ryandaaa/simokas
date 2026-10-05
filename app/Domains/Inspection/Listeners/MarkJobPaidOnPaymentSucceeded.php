<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Listeners;

use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobPaid;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Events\PaymentSucceeded;
use Illuminate\Support\Facades\DB;

/**
 * Reaksi pembayaran inspeksi: job requested menjadi paid.
 *
 * Idempoten: pembaruan berkondisi (where status = requested) membuat event
 * ganda tidak berefek ganda. Pembayaran gagal atau tertunda tidak
 * memancarkan PaymentSucceeded sehingga tidak mengubah job (PAY-R10).
 *
 * Penanganan risiko uang: jika job sudah bukan requested (mis. cancelled
 * oleh pemohon, expired, atau tidak ditemukan), pembayaran di-refund
 * otomatis secara idempoten lewat kontrak Ledger::refundPayment.
 */
final class MarkJobPaidOnPaymentSucceeded
{
    public function __construct(
        private readonly Ledger $ledger,
    ) {}

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
            /** @var InspectionJob|null $job */
            $job = InspectionJob::query()->find($event->refId);

            // Jika job cancelled atau expired (bukan status requested lagi saat pembayaran tiba),
            // pembayaran di-refund otomatis secara idempoten lewat kontrak Ledger::refundPayment.
            if ($job instanceof InspectionJob && in_array($job->status, [JobStatus::Cancelled, JobStatus::Expired], true)) {
                $this->ledger->refundPayment($event->paymentId);
            }

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
