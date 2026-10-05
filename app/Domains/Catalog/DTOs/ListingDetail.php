<?php

declare(strict_types=1);

namespace App\Domains\Catalog\DTOs;

use App\Domains\Listing\Enums\FuelType;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\Transmission;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\ValueObjects\ApproximateLocation;
use Illuminate\Support\Carbon;

/**
 * Read-only view model for the public listing detail page (CAT-2).
 *
 * Exact fields (addressFull, chassisNumber, inspectionPlace, exactLat,
 * exactLng) are null unless the viewer passed authorization. The public
 * map only ever receives the rounded ApproximateLocation.
 */
final readonly class ListingDetail
{
    /**
     * @param  list<ListingDetailPhoto>  $photos
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $brand,
        public string $model,
        public VehicleType $vehicleType,
        public int $year,
        public int $price,
        public bool $negotiable,
        public int $odometerKm,
        public Transmission $transmission,
        public FuelType $fuel,
        public string $color,
        public ?string $description,
        public ?string $areaName,
        public ListingStatus $status,
        public bool $isInspected,
        public ?Carbon $lastInspectedAt,
        public string $sellerId,
        public array $photos,
        public ?ApproximateLocation $approximate,
        public bool $canSeePrivate,
        public bool $isOwner,
        public bool $canEdit,
        public ?string $viewerRole,
        public bool $isPubliclyVisible,
        public bool $isSold,
        public ?string $addressFull = null,
        public ?string $chassisNumber = null,
        public ?string $inspectionPlace = null,
        public ?float $exactLat = null,
        public ?float $exactLng = null,
    ) {}

    public function showInspectedBadge(): bool
    {
        // CAT-E9 / CAT-T12: badge only while the listing is flagged
        // inspected. Revocation (RES-4, D-05) clears the flag itself.
        return $this->isInspected;
    }
}
