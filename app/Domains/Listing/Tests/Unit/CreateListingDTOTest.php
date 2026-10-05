<?php

declare(strict_types=1);

use App\Domains\Listing\DTOs\CreateListingDTO;
use App\Domains\Listing\Enums\FuelType;
use App\Domains\Listing\Enums\Transmission;
use App\Domains\Listing\Enums\VehicleType;
use Tests\TestCase;

uses(TestCase::class);

it('creates DTO from array data', function () {
    $data = [
        'vehicle_type' => 'car',
        'title' => 'Toyota Avanza 1.3 G Manual 2018',
        'brand' => 'Toyota',
        'model' => 'Avanza',
        'year' => 2018,
        'price' => 135_000_000,
        'negotiable' => true,
        'odometer_km' => 62000,
        'transmission' => 'manual',
        'fuel' => 'petrol',
        'color' => 'Hitam',
        'inspection_place' => 'Rumah',
        'photos' => [],
        'slots' => [],
        'chassis_number' => 'MHKA1234567890123',
        'description' => 'Mulus',
        'area_name' => 'Tampan',
        'lat' => 0.5071,
        'lng' => 101.4478,
        'address_full' => 'Jl. HR Soebrantas',
        'cover_photo_index' => 1,
    ];

    $dto = CreateListingDTO::fromArray($data);

    expect($dto->vehicle_type)->toBe(VehicleType::Car)
        ->and($dto->transmission)->toBe(Transmission::Manual)
        ->and($dto->fuel)->toBe(FuelType::Petrol)
        ->and($dto->title)->toBe('Toyota Avanza 1.3 G Manual 2018')
        ->and($dto->chassis_number)->toBe('MHKA1234567890123')
        ->and($dto->lat)->toBe(0.5071)
        ->and($dto->lng)->toBe(101.4478)
        ->and($dto->cover_photo_index)->toBe(1);
});
