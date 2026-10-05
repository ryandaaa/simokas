<?php

declare(strict_types=1);

use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\ValueObjects\ApproximateLocation;
use Tests\TestCase;

uses(TestCase::class);

it('computes a public approximate point that differs from the exact point but stays within the documented distance', function () {
    $exactLat = 0.5071234;
    $exactLng = 101.4478123;

    $approx = ApproximateLocation::fromExact($exactLat, $exactLng);

    expect($approx->lat)->not->toBe($exactLat)
        ->and($approx->lng)->not->toBe($exactLng)
        ->and($approx->distanceMetersFrom($exactLat, $exactLng))->toBeLessThanOrEqual(ApproximateLocation::MAX_OFFSET_METERS);
});

it('is deterministic for the same exact point', function () {
    $first = ApproximateLocation::fromExact(0.4712345, 101.3812345);
    $second = ApproximateLocation::fromExact(0.4712345, 101.3812345);

    expect($second->lat)->toBe($first->lat)
        ->and($second->lng)->toBe($first->lng)
        ->and($second->toArray())->toBe($first->toArray());
});

it('exposes the approximate point on the listing model without leaking exact coordinates', function () {
    $listing = new Listing([
        'lat' => 0.5071234,
        'lng' => 101.4478123,
    ]);

    $approx = $listing->approximateLocation();

    expect($approx)->toBeInstanceOf(ApproximateLocation::class)
        ->and($approx->lat)->not->toBe(0.5071234)
        ->and($approx->distanceMetersFrom(0.5071234, 101.4478123))->toBeLessThanOrEqual(ApproximateLocation::MAX_OFFSET_METERS);

    $publicPayload = $approx->toArray();

    expect($publicPayload)->toBe(['lat' => $approx->lat, 'lng' => $approx->lng]);
});

it('returns null approximate point when the listing has no coordinates', function () {
    $listing = new Listing([
        'lat' => null,
        'lng' => null,
    ]);

    expect($listing->approximateLocation())->toBeNull();
});
