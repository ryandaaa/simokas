<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobExpired;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Contracts\Billing;
use App\Support\Clock;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Action terjadwal untuk mengedaluwarsakan job 'requested' yang melewati batas expires_at (JOB-R7, JOB-T09).
 *
 * Dijalankan tiap menit oleh scheduler.
 * Idempoten: job yang sudah expired tidak akan diproses ulang.
 */
final class ExpireRequestedJobs
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly ?Billing $billing = null,
    ) {}

    public function handle(): int
    {
        $now = Clock::now();
        $expiredCount = 0;

        /** @var \Illuminate\Database\Eloquent\Collection<int, InspectionJob> $jobs */
        $jobs = InspectionJob::query()
            ->where('status', JobStatus::Requested->value)
            ->where('expires_at', '<=', $now)
            ->get();

        foreach ($jobs as $job) {
            $expired = DB::transaction(function () use ($job, $now): bool {
                $affected = InspectionJob::query()
                    ->whereKey($job->getKey())
                    ->where('status', JobStatus::Requested->value)
                    ->where('expires_at', '<=', $now)
                    ->update([
                        'status' => JobStatus::Expired->value,
                        'updated_at' => $now,
                    ]);

                if ($affected === 0) {
                    return false;
                }

                // Batalkan tagihan bila kontrak Billing menyediakan method pembatalan
                if ($this->billing !== null && method_exists($this->billing, 'cancelBill')) {
                    $this->billing->cancelBill((string) $job->getKey());
                }

                $this->events->dispatch(new JobExpired(
                    jobId: (string) $job->getKey(),
                    listingId: (string) $job->listing_id,
                    requesterId: (string) $job->requester_id,
                ));

                return true;
            });

            if ($expired) {
                $expiredCount++;
            }
        }

        return $expiredCount;
    }
}
