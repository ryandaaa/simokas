<?php

declare(strict_types=1);

namespace App\Domains\Rating\Contracts;

interface RatingReader
{
    /**
     * @return array{ratingAvg: float, ratingCount: int}
     */
    public function getInspectorRating(string $inspectorUserId): array;
}
