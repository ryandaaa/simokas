<?php

declare(strict_types=1);

namespace App\Domains\Listing\ValueObjects;

/**
 * Public, intentionally imprecise location derived from exact coordinates.
 *
 * PRD 16.1: the public only sees an approximate area, never the exact pin.
 * Coordinates are rounded to 2 decimals (about 1.1 km grid at the equator),
 * so the worst-case offset stays under MAX_OFFSET_METERS. The mapping is
 * deterministic, which keeps catalog pages stable. CAT-2 will use this for
 * the public area map without an exact marker.
 */
final readonly class ApproximateLocation
{
    /**
     * Maximum distance in metres between the exact and approximate point.
     * Rounding to 2 decimals shifts at most 0.005 degrees on each axis
     * (about 556 m), hence at most about 786 m diagonally; 800 m documents
     * that bound with a small margin.
     */
    public const int MAX_OFFSET_METERS = 800;

    public function __construct(
        public float $lat,
        public float $lng,
    ) {}

    public static function fromExact(float $lat, float $lng): self
    {
        return new self(round($lat, 2), round($lng, 2));
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function toArray(): array
    {
        return ['lat' => $this->lat, 'lng' => $this->lng];
    }

    public function distanceMetersFrom(float $lat, float $lng): float
    {
        $earthRadiusMeters = 6_371_000.0;

        $latFrom = deg2rad($lat);
        $latTo = deg2rad($this->lat);
        $latDelta = $latTo - $latFrom;
        $lngDelta = deg2rad($this->lng) - deg2rad($lng);

        $haversine = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadiusMeters * asin(min(1.0, sqrt($haversine)));
    }
}
