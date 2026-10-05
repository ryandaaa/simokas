<?php

declare(strict_types=1);

namespace App\Domains\Inspection\DTOs;

use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\VehicleType;
use Illuminate\Support\Carbon;

final readonly class OpenJobCard
{
    public function __construct(
        public string $id,
        public string $listingId,
        public string $vehicleType,
        public string $brand,
        public string $model,
        public int $year,
        public ?string $areaName,
        public int $feeAmount,
        public ?Carbon $availableSince,
        public ?Carbon $createdAt,
    ) {}

    public static function fromModel(InspectionJob $job): self
    {
        $listing = $job->listing;
        $vehicleType = $listing?->vehicle_type instanceof VehicleType
            ? $listing->vehicle_type->value
            : (string) ($listing?->vehicle_type ?? '');

        return new self(
            id: (string) $job->getKey(),
            listingId: (string) $job->listing_id,
            vehicleType: $vehicleType,
            brand: (string) ($listing?->brand ?? ''),
            model: (string) ($listing?->model ?? ''),
            year: (int) ($listing?->year ?? 0),
            areaName: $listing?->area_name,
            feeAmount: (int) $job->fee_amount,
            availableSince: $job->available_since,
            createdAt: $job->created_at,
        );
    }
}
