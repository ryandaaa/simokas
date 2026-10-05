<?php

declare(strict_types=1);

namespace App\Domains\Listing\DTOs;

/**
 * Requested changes for UpdateListing.
 *
 * Every entry is optional: only provided keys are changed, everything else
 * is left untouched. Unknown keys (seller_id, status, is_inspected, ...)
 * are ignored so ownership and system columns cannot be manipulated.
 *
 * Scalar field keys: vehicle_type, title, brand, model, year, price,
 * negotiable, odometer_km, transmission, fuel, color, chassis_number,
 * description, area_name, lat, lng, address_full, inspection_place.
 * Enum fields accept the enum instance or its string value.
 * Nullable columns (chassis_number, description) treat '' as null (clear).
 */
final readonly class UpdateListingDTO
{
    /**
     * @param  array<string, mixed>  $fields  Scalar changes keyed by column intent.
     * @param  array<int, array{slot_start: mixed, note?: string|null}>|null  $slots  Null keeps slots, otherwise replaces all.
     * @param  array<int, string>  $removePhotoIds  Existing photo ids to delete.
     * @param  array<int, mixed>  $newPhotos  Uploaded files to append, validated inside the Action.
     * @param  int|null  $coverNewPhotoIndex  Index into $newPhotos to use as cover.
     */
    public function __construct(
        public array $fields = [],
        public ?array $slots = null,
        public array $removePhotoIds = [],
        public array $newPhotos = [],
        public ?string $coverPhotoId = null,
        public ?int $coverNewPhotoIndex = null,
    ) {}
}
