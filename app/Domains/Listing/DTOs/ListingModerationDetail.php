<?php

declare(strict_types=1);

namespace App\Domains\Listing\DTOs;

final readonly class ListingModerationDetail
{
    /**
     * @param  array<int, array{id: string, url: string, sort_order: int, is_cover: bool}>  $photos
     * @param  array<int, array{slot_start: string, note: string|null}>  $slots
     */
    public function __construct(
        public string $id,
        public string $sellerId,
        public string $sellerName,
        public string $title,
        public string $vehicleType,
        public string $brand,
        public string $model,
        public int $year,
        public int $price,
        public bool $negotiable,
        public int $odometerKm,
        public string $transmission,
        public string $fuel,
        public string $color,
        public ?string $chassisNumber,
        public ?string $description,
        public ?string $areaName,
        public ?string $lat,
        public ?string $lng,
        public ?string $addressFull,
        public string $inspectionPlace,
        public string $status,
        public ?string $rejectionReason,
        public bool $isInspected,
        public ?string $lastInspectedAt,
        public array $photos,
        public array $slots,
        public string $createdAt,
    ) {}
}
