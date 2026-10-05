<?php

declare(strict_types=1);

namespace App\Domains\Listing\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class ListingInspected
{
    use Dispatchable;

    public function __construct(
        public readonly string $listingId,
        public readonly string $sellerId,
        public readonly string $inspectedAt,
    ) {}
}
