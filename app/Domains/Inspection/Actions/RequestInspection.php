<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Exceptions\ListingHasActiveJobException;
use App\Domains\Inspection\Exceptions\ListingNotActiveException;
use App\Domains\Inspection\Exceptions\RequestNotAllowedException;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Contracts\Billing;
use App\Domains\Payment\DTOs\BillData;
use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Setting\Contracts\SettingsReader;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Pemohon mengajukan inspeksi pada listing aktif (JOB-1).
 *
 * Tarif dan persentase platform disalin dari pengaturan saat job dibuat
 * (JOB-R6), sehingga perubahan tarif admin tidak memengaruhi job lama.
 * Nominal tagihan selalu dari server, tidak pernah dari input klien.
 */
final class RequestInspection
{
    public function __construct(
        private readonly SettingsReader $settings,
        private readonly Billing $billing,
    ) {}

    public static function idempotencyKeyFor(string $jobId): string
    {
        return "inspection-job-{$jobId}";
    }

    /**
     * @return array{job: InspectionJob, bill: BillData, reused: bool}
     */
    public function handle(User $requester, Listing $listing): array
    {
        $this->assertRequesterAllowed($requester);

        $listing->refresh();

        if ($listing->status !== ListingStatus::Active) {
            throw ListingNotActiveException::create($listing->status->value);
        }

        return DB::transaction(function () use ($requester, $listing): array {
            $existing = InspectionJob::query()
                ->where('listing_id', $listing->getKey())
                ->active()
                ->latest()
                ->first();

            if ($existing instanceof InspectionJob) {
                if ($existing->requester_id !== $requester->getKey()) {
                    throw ListingHasActiveJobException::create();
                }

                return [
                    'job' => $existing,
                    'bill' => $this->billFor($requester, $existing),
                    'reused' => true,
                ];
            }

            try {
                $job = InspectionJob::create([
                    'listing_id' => $listing->getKey(),
                    'requester_id' => $requester->getKey(),
                    'inspector_id' => null,
                    'status' => JobStatus::Requested,
                    'fee_amount' => $this->feeFor($listing->vehicle_type),
                    'platform_fee_pct' => $this->settings->getPlatformFeePct(),
                    'expires_at' => now()->addMinutes(30),
                ]);
            } catch (QueryException $e) {
                throw $this->isUniqueViolation($e)
                    ? ListingHasActiveJobException::create()
                    : $e;
            }

            return [
                'job' => $job,
                'bill' => $this->billFor($requester, $job),
                'reused' => false,
            ];
        });
    }

    private function assertRequesterAllowed(User $requester): void
    {
        if ($requester->isBlocked() || ! $requester->isActive()) {
            throw RequestNotAllowedException::blocked();
        }

        if ($requester->isInspector()) {
            throw RequestNotAllowedException::inspector();
        }

        if ($requester->isAdmin()) {
            throw RequestNotAllowedException::admin();
        }
    }

    private function feeFor(VehicleType $type): int
    {
        return match ($type) {
            VehicleType::Car => $this->settings->getInspectionFeeCar(),
            VehicleType::Motorcycle => $this->settings->getInspectionFeeMotorcycle(),
        };
    }

    private function billFor(User $requester, InspectionJob $job): BillData
    {
        return $this->billing->createBill(new CreateBillData(
            payerId: $requester->getKey(),
            purpose: PaymentPurpose::Inspection,
            refId: $job->getKey(),
            amount: (int) $job->fee_amount,
            idempotencyKey: self::idempotencyKeyFor($job->getKey()),
            expiresAt: $job->expires_at,
        ));
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        if (($e->errorInfo[0] ?? null) === '23000') {
            return true;
        }

        return str_contains(strtolower($e->getMessage()), 'duplicate');
    }
}
