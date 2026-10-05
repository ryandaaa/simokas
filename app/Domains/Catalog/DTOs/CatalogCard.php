<?php

declare(strict_types=1);

namespace App\Domains\Catalog\DTOs;

use App\Domains\Listing\Models\Listing;
use Illuminate\Support\Carbon;

/**
 * Public card view model. Only carries fields that are safe to
 * render for guests; exact coordinates, full address, chassis
 * number, and inspection place are never loaded (see BrowseListings).
 */
final readonly class CatalogCard
{
    public function __construct(
        public string $id,
        public string $title,
        public int $price,
        public ?int $year = null,
        public ?int $mileage = null,
        public ?string $area = null,
        public ?string $imageUrl = null,
        public ?string $imageAlt = null,
        public bool $inspected = false,
        public ?Carbon $inspectedDate = null,
    ) {}

    public static function fromModel(Listing $listing): self
    {
        $cover = $listing->relationLoaded('coverPhoto') ? $listing->coverPhoto : null;

        return new self(
            id: (string) $listing->getKey(),
            title: (string) $listing->getAttribute('title'),
            price: (int) $listing->getAttribute('price'),
            year: $listing->getAttribute('year') !== null ? (int) $listing->getAttribute('year') : null,
            mileage: $listing->getAttribute('odometer_km') !== null ? (int) $listing->getAttribute('odometer_km') : null,
            area: $listing->getAttribute('area_name'),
            imageUrl: $cover !== null
                ? route('listings.photos.show', ['listing' => $listing->getKey(), 'photo' => $cover->getKey()])
                : null,
            imageAlt: (string) $listing->getAttribute('title'),
            inspected: (bool) $listing->getAttribute('is_inspected'),
            inspectedDate: $listing->getAttribute('last_inspected_at'),
        );
    }
}
