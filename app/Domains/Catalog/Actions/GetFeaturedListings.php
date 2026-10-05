<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\DTOs\CatalogCard;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;

/**
 * Read-only featured listings for the public home page (CAT-3).
 *
 * Only `active` listings are returned: photo access for guests is
 * granted solely for active listings by the Listing policy, so
 * featuring `in_inspection` or `in_transaction` items would render
 * broken cover images for guests until that policy is revised
 * (tracked separately). Only safe columns are selected, so private
 * fields are never loaded (see BrowseListings::SAFE_COLUMNS).
 */
final class GetFeaturedListings
{
    public const int LIMIT = 6;

    /**
     * @return list<CatalogCard>
     */
    public function handle(int $limit = self::LIMIT): array
    {
        $limit = max(1, min($limit, self::LIMIT));

        return Listing::query()
            ->select(BrowseListings::SAFE_COLUMNS)
            ->where('status', ListingStatus::Active)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->with('coverPhoto')
            ->limit($limit)
            ->get()
            ->map(fn (Listing $listing): CatalogCard => CatalogCard::fromModel($listing))
            ->all();
    }
}
