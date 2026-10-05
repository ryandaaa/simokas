<?php

declare(strict_types=1);

namespace App\Domains\Listing\DTOs;

use App\Domains\Listing\Enums\FuelType;
use App\Domains\Listing\Enums\Transmission;
use App\Domains\Listing\Enums\VehicleType;
use Illuminate\Http\UploadedFile;

final readonly class CreateListingDTO
{
    /**
     * @param  array<int, UploadedFile|array{file: UploadedFile, is_cover?: bool, sort_order?: int}>  $photos
     * @param  array<int, array{slot_start: mixed, note?: string|null}>  $slots
     */
    public function __construct(
        public VehicleType|string $vehicle_type,
        public string $title,
        public string $brand,
        public string $model,
        public int $year,
        public int $price,
        public bool $negotiable,
        public int $odometer_km,
        public Transmission|string $transmission,
        public FuelType|string $fuel,
        public string $color,
        public string $inspection_place,
        public array $photos,
        public array $slots,
        public ?string $chassis_number = null,
        public ?string $description = null,
        public ?string $area_name = null,
        public ?float $lat = null,
        public ?float $lng = null,
        public ?string $address_full = null,
        public ?int $cover_photo_index = 0,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            vehicle_type: $data['vehicle_type'] instanceof VehicleType
                ? $data['vehicle_type']
                : VehicleType::from((string) $data['vehicle_type']),
            title: (string) $data['title'],
            brand: (string) $data['brand'],
            model: (string) $data['model'],
            year: (int) $data['year'],
            price: (int) $data['price'],
            negotiable: (bool) ($data['negotiable'] ?? true),
            odometer_km: (int) $data['odometer_km'],
            transmission: $data['transmission'] instanceof Transmission
                ? $data['transmission']
                : Transmission::from((string) $data['transmission']),
            fuel: $data['fuel'] instanceof FuelType
                ? $data['fuel']
                : FuelType::from((string) $data['fuel']),
            color: (string) $data['color'],
            inspection_place: (string) $data['inspection_place'],
            photos: (array) ($data['photos'] ?? []),
            slots: (array) ($data['slots'] ?? []),
            chassis_number: isset($data['chassis_number']) && $data['chassis_number'] !== '' ? (string) $data['chassis_number'] : null,
            description: isset($data['description']) && $data['description'] !== '' ? (string) $data['description'] : null,
            area_name: isset($data['area_name']) && $data['area_name'] !== '' ? (string) $data['area_name'] : null,
            lat: isset($data['lat']) && $data['lat'] !== '' ? (float) $data['lat'] : null,
            lng: isset($data['lng']) && $data['lng'] !== '' ? (float) $data['lng'] : null,

            address_full: isset($data['address_full']) && $data['address_full'] !== '' ? (string) $data['address_full'] : null,
            cover_photo_index: isset($data['cover_photo_index']) ? (int) $data['cover_photo_index'] : 0,
        );
    }
}
