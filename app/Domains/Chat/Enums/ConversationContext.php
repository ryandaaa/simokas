<?php

declare(strict_types=1);

namespace App\Domains\Chat\Enums;

enum ConversationContext: string
{
    case Listing = 'listing';
    case Job = 'job';

    public function label(): string
    {
        return match ($this) {
            self::Listing => 'Listing',
            self::Job => 'Inspeksi',
        };
    }
}
