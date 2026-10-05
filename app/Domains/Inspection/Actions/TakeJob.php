<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobTaken;
use App\Domains\Inspection\Exceptions\InspectorNotVerifiedException;
use App\Domains\Inspection\Exceptions\InspectorSpecialtyMismatchException;
use App\Domains\Inspection\Exceptions\JobAlreadyTakenException;
use App\Domains\Inspection\Exceptions\MaxActiveJobsException;
use App\Domains\Inspection\Exceptions\OwnListingJobException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Contracts\InspectorEligibility;
use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

/**
 * Action atomik untuk inspektor mengambil job inspeksi (JOB-2).
 *
 * Menggunakan pembaruan berkondisi `UPDATE ... WHERE status = 'paid' AND inspector_id IS NULL`
 * di dalam DB::transaction. Serialisasi per inspektor (lockForUpdate pada baris User inspektor)
 * menjamin batas maksimal 3 job aktif aman dari rebutan konkurensi.
 */
final class TakeJob
{
    public const int MAX_ACTIVE_JOBS = 3;

    public function __construct(
        private readonly InspectorEligibility $eligibility,
        private readonly Dispatcher $events,
    ) {}

    public function handle(User $inspector, InspectionJob $job): InspectionJob
    {
        return DB::transaction(function () use ($inspector, $job): InspectionJob {
            // Serialisasi per inspektor untuk mencegah race condition pada batas job aktif
            User::query()->whereKey($inspector->getKey())->lockForUpdate()->first();

            if (! $this->eligibility->canViewAndTakeJobs($inspector)) {
                throw InspectorNotVerifiedException::create();
            }

            /** @var Listing|null $listing */
            $listing = Listing::query()
                ->select(['id', 'seller_id', 'vehicle_type'])
                ->whereKey($job->listing_id)
                ->first();

            if ($listing !== null && (string) $listing->seller_id === (string) $inspector->getKey()) {
                throw OwnListingJobException::create();
            }

            $allowedSpecialties = $this->eligibility->getAllowedSpecialties($inspector);
            $vehicleType = $listing instanceof Listing
                ? $listing->vehicle_type->value
                : '';

            if (! in_array($vehicleType, $allowedSpecialties, true)) {
                throw InspectorSpecialtyMismatchException::create();
            }

            $activeCount = InspectionJob::query()
                ->where('inspector_id', $inspector->getKey())
                ->whereIn('status', [
                    JobStatus::Taken,
                    JobStatus::Scheduled,
                    JobStatus::InProgress,
                    JobStatus::ResultSubmitted,
                ])
                ->count();

            if ($activeCount >= self::MAX_ACTIVE_JOBS) {
                throw MaxActiveJobsException::create();
            }

            $affected = InspectionJob::query()
                ->whereKey($job->getKey())
                ->where('status', JobStatus::Paid)
                ->whereNull('inspector_id')
                ->update([
                    'inspector_id' => $inspector->getKey(),
                    'status' => JobStatus::Taken,
                    'taken_at' => now(),
                ]);

            if ($affected === 0) {
                throw JobAlreadyTakenException::create();
            }

            $freshJob = $job->refresh();

            $this->events->dispatch(new JobTaken(
                jobId: (string) $freshJob->getKey(),
                listingId: (string) $freshJob->listing_id,
                inspectorId: (string) $inspector->getKey(),
            ));

            return $freshJob;
        });
    }
}
