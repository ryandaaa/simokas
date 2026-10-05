<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\DTOs\CatalogCard;
use App\Domains\Catalog\DTOs\CatalogFilter;
use App\Domains\Listing\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-only catalog search. Never writes data (CAT-R6).
 *
 * Only safe listing columns are selected, so private fields
 * (lat, lng, address_full, chassis_number, inspection_place)
 * are never loaded from the database. All filter values come
 * from the sanitized CatalogFilter: sort columns use a whitelist,
 * keywords escape LIKE wildcards, and every value is bound as a
 * query parameter (no string interpolation into SQL).
 */
final class BrowseListings
{
    public const int PER_PAGE = 12;

    /**
     * Columns the catalog is allowed to read. Private location and
     * identity fields must never be added here.
     *
     * @var list<string>
     */
    public const array SAFE_COLUMNS = [
        'id',
        'vehicle_type',
        'title',
        'brand',
        'model',
        'year',
        'price',
        'odometer_km',
        'area_name',
        'status',
        'is_inspected',
        'last_inspected_at',
        'created_at',
    ];

    /**
     * @return LengthAwarePaginator<int, CatalogCard>
     */
    public function handle(CatalogFilter $filter): LengthAwarePaginator
    {
        $query = Listing::query()
            ->select(self::SAFE_COLUMNS)
            ->publiclyVisible()
            // Cover photo only (no private columns exist on listing_photos).
            // Note: the select list is intentionally left unconstrained:
            // constraining columns on the ofMany coverPhoto relation
            // breaks its aggregate join with an ambiguous column error.
            ->with('coverPhoto');

        $this->applyFilters($query, $filter);
        $this->applySort($query, $filter->sort);

        /** @var LengthAwarePaginator<int, Listing> $paginator */
        $paginator = $query->paginate(self::PER_PAGE, ['*'], 'page', $filter->page);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Listing $listing): CatalogCard => CatalogCard::fromModel($listing))
        );

        /** @var LengthAwarePaginator<int, CatalogCard> $paginator */
        return $paginator;
    }

    /**
     * @param  Builder<Listing>  $query
     */
    private function applyFilters(Builder $query, CatalogFilter $filter): void
    {
        if ($filter->keyword !== null) {
            $keyword = self::escapeLike($filter->keyword);
            $query->where(function (Builder $group) use ($keyword): void {
                $group->where('title', 'like', "%{$keyword}%")
                    ->orWhere('brand', 'like', "%{$keyword}%")
                    ->orWhere('model', 'like', "%{$keyword}%");
            });
        }

        if ($filter->type !== null) {
            $query->where('vehicle_type', $filter->type);
        }

        if ($filter->brand !== null) {
            $query->where('brand', 'like', '%'.self::escapeLike($filter->brand).'%');
        }

        if ($filter->priceMin !== null) {
            $query->where('price', '>=', $filter->priceMin);
        }

        if ($filter->priceMax !== null) {
            $query->where('price', '<=', $filter->priceMax);
        }

        if ($filter->yearMin !== null) {
            $query->where('year', '>=', $filter->yearMin);
        }

        if ($filter->yearMax !== null) {
            $query->where('year', '<=', $filter->yearMax);
        }

        if ($filter->area !== null) {
            $query->where('area_name', 'like', '%'.self::escapeLike($filter->area).'%');
        }

        if ($filter->inspected === 'inspected') {
            $query->where('is_inspected', true);
        } elseif ($filter->inspected === 'uninspected') {
            $query->where('is_inspected', false);
        }
    }

    /**
     * @param  Builder<Listing>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'cheapest' => $query->orderBy('price', 'asc')->orderBy('id', 'asc'),
            'priciest' => $query->orderBy('price', 'desc')->orderBy('id', 'asc'),
            'lowest_km' => $query->orderBy('odometer_km', 'asc')->orderBy('id', 'asc'),
            default => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
        };
    }

    private static function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
