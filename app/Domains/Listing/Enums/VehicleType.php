<?php

declare(strict_types=1);

namespace App\Domains\Listing\Enums;

enum VehicleType: string
{
    case Car = 'car';
    case Motorcycle = 'motorcycle';

    public function label(): string
    {
        return match ($this) {
            self::Car => __('listing.vehicle_types.car'),
            self::Motorcycle => __('listing.vehicle_types.motorcycle'),
        };
    }
}
