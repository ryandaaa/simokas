<?php

declare(strict_types=1);

namespace App\Domains\Catalog\DTOs;

/**
 * Single photo on the listing detail page. The URL always points at the
 * authorized photo route (listings.photos.show); the file bytes are never
 * embedded in the page.
 */
final readonly class ListingDetailPhoto
{
    public function __construct(
        public string $id,
        public string $url,
        public string $alt,
    ) {}
}
