<?php

declare(strict_types=1);

use App\Domains\Listing\Enums\FuelType;
use Tests\TestCase;

uses(TestCase::class);

it('has expected fuel type cases and labels', function () {
    expect(FuelType::Petrol->value)->toBe('petrol')
        ->and(FuelType::Diesel->value)->toBe('diesel')
        ->and(FuelType::Electric->value)->toBe('electric')
        ->and(FuelType::Hybrid->value)->toBe('hybrid')
        ->and(FuelType::Petrol->label())->toBe('Bensin')
        ->and(FuelType::Diesel->label())->toBe('Diesel')
        ->and(FuelType::Electric->label())->toBe('Listrik')
        ->and(FuelType::Hybrid->label())->toBe('Hybrid');
});
