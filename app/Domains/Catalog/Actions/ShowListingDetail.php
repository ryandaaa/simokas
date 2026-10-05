<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Contracts\InspectionJobAccess;
use App\Domains\Catalog\DTOs\ListingDetail;
use App\Domains\Catalog\DTOs\ListingDetailPhoto;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\ValueObjects\ApproximateLocation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Read-only listing detail for the public catalog (CAT-R6, CAT-R7).
 *
 * Visibility: listings with status active, in_inspection, in_transaction,
 * or sold are readable by anyone; any other status throws a 404 unless the
 * viewer is the owner or an admin (CAT-E4, CAT-E5).
 *
 * Privacy: the public query selects only safe columns (mirroring
 * BrowseListings::SAFE_COLUMNS). The approximate map point is computed in
 * SQL with ROUND() so exact coordinates are never loaded for public
 * viewers. Private columns are loaded in a second query only after
 * ListingPolicy::viewPrivate or the InspectionJobAccess seam passes.
 *
 * NOTE (shared, for Listing): ListingPolicy::view and ::viewPhoto currently
 * allow only `active` listings publicly, while CAT-R1/CAT-E5/LST-E11 require
 * in_inspection, in_transaction, and sold to be publicly readable too
 * (photos included). Catalog therefore enforces its own visibility rule
 * here instead of reusing the policy. The policy should be widened in a
 * shared PR; until then photo URLs for non-active public listings may 403.
 */
final class ShowListingDetail
{
    /**
     * Public columns the detail page may read. Private location and
     * identity fields must never be added here.
     *
     * @var list<string>
     */
    public const array SAFE_COLUMNS = [
        'id',
        'seller_id',
        'vehicle_type',
        'title',
        'brand',
        'model',
        'year',
        'price',
        'negotiable',
        'odometer_km',
        'transmission',
        'fuel',
        'color',
        'description',
        'area_name',
        'status',
        'is_inspected',
        'last_inspected_at',
        'created_at',
    ];

    /**
     * Exact fields, loaded only after authorization passes.
     *
     * @var list<string>
     */
    public const array PRIVATE_COLUMNS = [
        'address_full',
        'chassis_number',
        'inspection_place',
        'lat',
        'lng',
    ];

    /**
     * Statuses anyone may open. `sold` renders as a Terjual page without
     * action buttons (CAT-E5); the rest render normally (CAT-R1).
     *
     * @var list<ListingStatus>
     */
    public const array PUBLIC_STATUSES = [
        ListingStatus::Active,
        ListingStatus::InInspection,
        ListingStatus::InTransaction,
        ListingStatus::Sold,
    ];

    public function __construct(private readonly InspectionJobAccess $jobAccess) {}

    public function handle(string $listingId, ?User $viewer): ListingDetail
    {
        if (! Str::isUuid($listingId)) {
            throw (new ModelNotFoundException)->setModel(Listing::class, $listingId);
        }

        $guard = Listing::query()
            ->select(['id', 'seller_id', 'status'])
            ->whereKey($listingId)
            ->first();

        if ($guard === null) {
            throw (new ModelNotFoundException)->setModel(Listing::class, $listingId);
        }

        /** @var ListingStatus $status */
        $status = $guard->getAttribute('status');
        $isPublic = in_array($status, self::PUBLIC_STATUSES, true);

        $isOwner = $viewer !== null
            && $viewer->getKey() === $guard->getAttribute('seller_id')
            && $viewer->isActive()
            && ! $viewer->isBlocked();

        $isAdmin = $viewer !== null
            && $viewer->isAdmin()
            && $viewer->isActive()
            && ! $viewer->isBlocked();

        if (! $isPublic && ! $isOwner && ! $isAdmin) {
            throw (new ModelNotFoundException)->setModel(Listing::class, $listingId);
        }

        /** @var Listing $listing */
        $listing = Listing::query()
            ->select(self::SAFE_COLUMNS)
            ->with(['photos' => fn ($query) => $query->orderBy('sort_order')])
            ->whereKey($listingId)
            ->firstOrFail();

        $canSeePrivate = $viewer !== null
            && (Gate::forUser($viewer)->allows('viewPrivate', $guard)
                || $this->jobAccess->canViewPrivate($viewer, $guard));

        $private = $canSeePrivate
            ? Listing::query()->select(array_merge(['id'], self::PRIVATE_COLUMNS))->whereKey($listingId)->first()
            : null;

        $approximate = $this->approximateLocation($listingId);

        $photos = $listing->photos->map(fn ($photo): ListingDetailPhoto => new ListingDetailPhoto(
            id: (string) $photo->getKey(),
            url: route('listings.photos.show', ['listing' => $listing->getKey(), 'photo' => $photo->getKey()]),
            alt: $this->photoAlt($listing, $photo->getAttribute('sort_order')),
        ))->all();

        return new ListingDetail(
            id: (string) $listing->getKey(),
            title: (string) $listing->getAttribute('title'),
            brand: (string) $listing->getAttribute('brand'),
            model: (string) $listing->getAttribute('model'),
            vehicleType: $listing->getAttribute('vehicle_type'),
            year: (int) $listing->getAttribute('year'),
            price: (int) $listing->getAttribute('price'),
            negotiable: (bool) $listing->getAttribute('negotiable'),
            odometerKm: (int) $listing->getAttribute('odometer_km'),
            transmission: $listing->getAttribute('transmission'),
            fuel: $listing->getAttribute('fuel'),
            color: (string) $listing->getAttribute('color'),
            description: $listing->getAttribute('description'),
            areaName: $listing->getAttribute('area_name'),
            status: $status,
            isInspected: (bool) $listing->getAttribute('is_inspected'),
            lastInspectedAt: $listing->getAttribute('last_inspected_at'),
            sellerId: (string) $guard->getAttribute('seller_id'),
            photos: $photos,
            approximate: $approximate,
            canSeePrivate: $canSeePrivate,
            isOwner: $isOwner,
            canEdit: $viewer !== null && Gate::forUser($viewer)->allows('update', $guard),
            viewerRole: $viewer?->role->value,
            isPubliclyVisible: $isPublic,
            isSold: $status === ListingStatus::Sold,
            addressFull: $private?->getAttribute('address_full'),
            chassisNumber: $private?->getAttribute('chassis_number'),
            inspectionPlace: $private?->getAttribute('inspection_place'),
            exactLat: $private?->getAttribute('lat') !== null ? (float) $private->getAttribute('lat') : null,
            exactLng: $private?->getAttribute('lng') !== null ? (float) $private->getAttribute('lng') : null,
        );
    }

    /**
     * Rounded area point computed in SQL so exact coordinates never enter
     * the public model. Matches ApproximateLocation::fromExact (2 decimals).
     */
    private function approximateLocation(string $listingId): ?ApproximateLocation
    {
        $row = Listing::query()
            ->selectRaw('ROUND(lat, 2) as approx_lat, ROUND(lng, 2) as approx_lng')
            ->whereKey($listingId)
            ->first();

        if ($row === null || $row->getAttribute('approx_lat') === null || $row->getAttribute('approx_lng') === null) {
            return null;
        }

        return new ApproximateLocation(
            (float) $row->getAttribute('approx_lat'),
            (float) $row->getAttribute('approx_lng'),
        );
    }

    private function photoAlt(Listing $listing, mixed $sortOrder): string
    {
        $position = ((int) $sortOrder) + 1;
        $total = max($listing->photos->count(), 1);

        return "Foto {$position} dari {$total} — {$listing->getAttribute('title')}";
    }
}
