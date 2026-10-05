<?php

declare(strict_types=1);

namespace App\Domains\Listing\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\ApproveListing;
use App\Domains\Listing\Actions\RejectListing;
use App\Domains\Listing\Contracts\ListingModerationContract;
use App\Domains\Listing\DTOs\ListingModerationDetail;
use App\Domains\Listing\DTOs\ListingModerationQueueItem;
use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListingModerationService implements ListingModerationContract
{
    public function __construct(
        private readonly ApproveListing $approveListing,
        private readonly RejectListing $rejectListing,
    ) {}

    public function queue(int $perPage = 12): LengthAwarePaginator
    {
        return Listing::query()
            ->pendingReview()
            ->with(['seller:id,name', 'coverPhoto'])
            ->oldest()
            ->paginate($perPage)
            ->through(fn (Listing $listing): ListingModerationQueueItem => $this->toQueueItem($listing));
    }

    public function detail(string $listingId): ListingModerationDetail
    {
        return $this->toDetail($this->findListing($listingId));
    }

    public function approve(User $admin, string $listingId): ListingModerationDetail
    {
        $listing = $this->approveListing->handle($admin, $this->findListing($listingId));

        return $this->toDetail($listing->load(['seller:id,name', 'photos', 'inspectionSlots']));
    }

    public function reject(User $admin, string $listingId, string $reason): ListingModerationDetail
    {
        $listing = $this->rejectListing->handle($admin, $this->findListing($listingId), $reason);

        return $this->toDetail($listing->load(['seller:id,name', 'photos', 'inspectionSlots']));
    }

    private function findListing(string $listingId): Listing
    {
        /** @var Listing $listing */
        $listing = Listing::query()
            ->with(['seller:id,name', 'photos', 'inspectionSlots'])
            ->findOrFail($listingId);

        return $listing;
    }

    private function toQueueItem(Listing $listing): ListingModerationQueueItem
    {
        return new ListingModerationQueueItem(
            id: $listing->id,
            sellerId: $listing->seller_id,
            sellerName: $listing->seller->name,
            title: $listing->title,
            brand: $listing->brand,
            model: $listing->model,
            year: $listing->year,
            price: $listing->price,
            areaName: $listing->area_name,
            coverUrl: $listing->coverPhoto !== null
                ? route('listings.photos.show', ['listing' => $listing->id, 'photo' => $listing->coverPhoto->id])
                : null,
            createdAt: $listing->created_at->toIso8601String(),
        );
    }

    private function toDetail(Listing $listing): ListingModerationDetail
    {
        return new ListingModerationDetail(
            id: $listing->id,
            sellerId: $listing->seller_id,
            sellerName: $listing->seller->name,
            title: $listing->title,
            vehicleType: $listing->vehicle_type->value,
            brand: $listing->brand,
            model: $listing->model,
            year: $listing->year,
            price: $listing->price,
            negotiable: $listing->negotiable,
            odometerKm: $listing->odometer_km,
            transmission: $listing->transmission->value,
            fuel: $listing->fuel->value,
            color: $listing->color,
            chassisNumber: $listing->chassis_number,
            description: $listing->description,
            areaName: $listing->area_name,
            lat: $listing->lat !== null ? (string) $listing->lat : null,
            lng: $listing->lng !== null ? (string) $listing->lng : null,
            addressFull: $listing->address_full,
            inspectionPlace: $listing->inspection_place,
            status: $listing->status->value,
            rejectionReason: $listing->rejection_reason,
            isInspected: $listing->is_inspected,
            lastInspectedAt: $listing->last_inspected_at?->toIso8601String(),
            photos: $listing->photos->map(fn ($photo): array => [
                'id' => $photo->id,
                'url' => route('listings.photos.show', ['listing' => $listing->id, 'photo' => $photo->id]),
                'sort_order' => $photo->sort_order,
                'is_cover' => $photo->is_cover,
            ])->all(),
            slots: $listing->inspectionSlots->map(fn ($slot): array => [
                'slot_start' => $slot->slot_start->toIso8601String(),
                'note' => $slot->note,
            ])->all(),
            createdAt: $listing->created_at->toIso8601String(),
        );
    }
}
