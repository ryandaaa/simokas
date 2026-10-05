<?php

declare(strict_types=1);

namespace App\Domains\Listing\Enums;

enum Transmission: string
{
    case Manual = 'manual';
    case Automatic = 'automatic';

    public function label(): string
    {
        return match ($this) {
            self::Manual => __('listing.transmissions.manual'),
            self::Automatic => __('listing.transmissions.automatic'),
        };
    }
}
