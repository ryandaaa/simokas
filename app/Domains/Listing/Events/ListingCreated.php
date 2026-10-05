<?php

declare(strict_types=1);

namespace App\Domains\Listing\Events;

use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Events\Dispatchable;

final class ListingCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Listing $listing,
    ) {}
}
