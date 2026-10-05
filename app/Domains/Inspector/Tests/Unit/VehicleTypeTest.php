<?php

declare(strict_types=1);

use App\Domains\Inspector\Enums\VehicleType;
use Tests\TestCase;

uses(TestCase::class);

it('has expected vehicle type cases', function () {
    expect(VehicleType::Car->value)->toBe('car')
        ->and(VehicleType::Motorcycle->value)->toBe('motorcycle');
});

it('returns non-empty labels for all vehicle types', function (VehicleType $type) {
    expect($type->label())->not->toBeEmpty();
})->with(VehicleType::cases());
