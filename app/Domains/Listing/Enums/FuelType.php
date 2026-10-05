<?php

declare(strict_types=1);

namespace App\Domains\Listing\Enums;

enum FuelType: string
{
    case Petrol = 'petrol';
    case Diesel = 'diesel';
    case Electric = 'electric';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::Petrol => __('listing.fuel_types.petrol'),
            self::Diesel => __('listing.fuel_types.diesel'),
            self::Electric => __('listing.fuel_types.electric'),
            self::Hybrid => __('listing.fuel_types.hybrid'),
        };
    }
}
