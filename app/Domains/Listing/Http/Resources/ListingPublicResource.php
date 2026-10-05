<?php

declare(strict_types=1);

namespace App\Domains\Listing\Http\Resources;

use App\Domains\Listing\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Listing
 */
final class ListingPublicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Note: lat, lng, address_full, chassis_number, and inspection_place are
     * strictly excluded from public resources (inspection_place may contain
     * the seller's home address, so it is private per PRD 13.3 and 19).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_type' => $this->vehicle_type->value,
            'title' => $this->title,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'price' => $this->price,
            'negotiable' => $this->negotiable,
            'odometer_km' => $this->odometer_km,
            'transmission' => $this->transmission->value,
            'fuel' => $this->fuel->value,
            'color' => $this->color,
            'description' => $this->description,
            'area_name' => $this->area_name,
            'status' => $this->status->value,
            'is_inspected' => $this->is_inspected,
            'last_inspected_at' => $this->last_inspected_at?->toIso8601String(),
            'cover_url' => $this->coverPhoto !== null
                ? route('listings.photos.show', ['listing' => $this->id, 'photo' => $this->coverPhoto->id])
                : null,
            'photos' => $this->photos->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => route('listings.photos.show', ['listing' => $this->id, 'photo' => $photo->id]),
                'sort_order' => $photo->sort_order,
                'is_cover' => $photo->is_cover,
            ])->all(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
