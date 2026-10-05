<?php

declare(strict_types=1);

namespace App\Domains\Listing\Http\Resources;

use App\Domains\Listing\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Listing
 */
final class ListingPrivateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller_id' => $this->seller_id,
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
            'chassis_number' => $this->chassis_number,
            'description' => $this->description,
            'area_name' => $this->area_name,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'address_full' => $this->address_full,
            'inspection_place' => $this->inspection_place,
            'status' => $this->status->value,
            'rejection_reason' => $this->rejection_reason,
            'is_inspected' => $this->is_inspected,
            'last_inspected_at' => $this->last_inspected_at?->toIso8601String(),
            'validated_by' => $this->validated_by,
            'validated_at' => $this->validated_at?->toIso8601String(),
            'cover_url' => $this->coverPhoto !== null
                ? route('listings.photos.show', ['listing' => $this->id, 'photo' => $this->coverPhoto->id])
                : null,
            'photos' => $this->photos->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => route('listings.photos.show', ['listing' => $this->id, 'photo' => $photo->id]),
                'sort_order' => $photo->sort_order,
                'is_cover' => $photo->is_cover,
            ])->all(),
            'inspection_slots' => $this->inspectionSlots->map(fn ($slot) => [
                'id' => $slot->id,
                'slot_start' => $slot->slot_start->toIso8601String(),
                'note' => $slot->note,
            ])->all(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
