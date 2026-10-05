<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Inspection\DTOs\OpenJobCard;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('maps InspectionJob model to OpenJobCard DTO with only safe fields', function () {
    $listing = Listing::factory()->active()->create([
        'vehicle_type' => VehicleType::Car,
        'brand' => 'Honda',
        'model' => 'Civic',
        'year' => 2022,
        'area_name' => 'Payung Sekaki',
        'address_full' => 'Jl. Rahasia No. 1',
        'chassis_number' => 'CHASSIS-1234',
        'lat' => '0.51',
        'lng' => '101.45',
        'inspection_place' => 'seller_home',
    ]);

    $job = InspectionJob::factory()->paid()->create([
        'listing_id' => $listing->id,
        'fee_amount' => 50000,
    ]);

    $card = OpenJobCard::fromModel($job);

    expect($card->id)->toBe((string) $job->id)
        ->and($card->listingId)->toBe((string) $listing->id)
        ->and($card->vehicleType)->toBe('car')
        ->and($card->brand)->toBe('Honda')
        ->and($card->model)->toBe('Civic')
        ->and($card->year)->toBe(2022)
        ->and($card->areaName)->toBe('Payung Sekaki')
        ->and($card->feeAmount)->toBe(50000);

    $cardArray = (array) $card;
    expect(array_key_exists('address_full', $cardArray))->toBeFalse()
        ->and(array_key_exists('chassis_number', $cardArray))->toBeFalse()
        ->and(array_key_exists('lat', $cardArray))->toBeFalse()
        ->and(array_key_exists('lng', $cardArray))->toBeFalse()
        ->and(array_key_exists('inspection_place', $cardArray))->toBeFalse();
});
