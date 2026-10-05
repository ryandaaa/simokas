<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Enums;

enum VehicleType: string
{
    case Car = 'car';
    case Motorcycle = 'motorcycle';

    public function label(): string
    {
        return match ($this) {
            self::Car => __('inspector.vehicle_types.car'),
            self::Motorcycle => __('inspector.vehicle_types.motorcycle'),
        };
    }
}
