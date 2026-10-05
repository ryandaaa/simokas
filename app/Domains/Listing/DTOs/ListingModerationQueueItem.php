<?php

declare(strict_types=1);

namespace App\Domains\Listing\DTOs;

final readonly class ListingModerationQueueItem
{
    public function __construct(
        public string $id,
        public string $sellerId,
        public string $sellerName,
        public string $title,
        public string $brand,
        public string $model,
        public int $year,
        public int $price,
        public ?string $areaName,
        public ?string $coverUrl,
        public string $createdAt,
    ) {}
}
