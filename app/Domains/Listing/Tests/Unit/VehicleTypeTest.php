<?php

declare(strict_types=1);

use App\Domains\Listing\Enums\VehicleType;
use Tests\TestCase;

uses(TestCase::class);

it('has expected vehicle type cases and labels', function () {
    expect(VehicleType::Car->value)->toBe('car')
        ->and(VehicleType::Motorcycle->value)->toBe('motorcycle')
        ->and(VehicleType::Car->label())->toBe('Mobil')
        ->and(VehicleType::Motorcycle->label())->toBe('Motor');
});
