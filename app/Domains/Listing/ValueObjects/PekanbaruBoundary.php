<?php

declare(strict_types=1);

namespace App\Domains\Listing\ValueObjects;

/**
 * Conservative bounding box for Kota Pekanbaru.
 *
 * [USULAN] PRD 16.1 only states that the point "must be inside the
 * Pekanbaru area" without providing official boundary data, and the team
 * forbids downloading boundary datasets from the internet for this card.
 * This box is a documented approximation that comfortably covers the city
 * (centre 0.5071, 101.4478) while rejecting clearly distant points such as
 * Jakarta or (0, 0).
 *
 * Replace the body of contains() with a proper polygon check (e.g. loaded
 * from an official GeoJSON file) without changing the public API when the
 * official boundary becomes available.
 */
final class PekanbaruBoundary
{
    public const float MIN_LAT = 0.40;

    public const float MAX_LAT = 0.63;

    public const float MIN_LNG = 101.28;

    public const float MAX_LNG = 101.76;

    public const float CENTRE_LAT = 0.5071;

    public const float CENTRE_LNG = 101.4478;

    public const int DEFAULT_ZOOM = 12;

    private function __construct() {}

    public static function contains(float $lat, float $lng): bool
    {
        return $lat >= self::MIN_LAT
            && $lat <= self::MAX_LAT
            && $lng >= self::MIN_LNG
            && $lng <= self::MAX_LNG;
    }
}
