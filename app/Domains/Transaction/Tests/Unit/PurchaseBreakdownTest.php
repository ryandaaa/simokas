<?php

declare(strict_types=1);

use App\Domains\Transaction\Support\PurchaseBreakdown;
use Tests\TestCase;

uses(TestCase::class);

it('computes the K2 commission breakdown for the documented example', function () {
    // Flow doc section 3: price 100.000.000, commission 3%, platform 10% of commission.
    $breakdown = PurchaseBreakdown::calculate(100000000, 3, 10);

    expect($breakdown)->toBe([
        'commission' => 3000000,
        'platform' => 300000,
        'inspector' => 2700000,
        'seller' => 97000000,
    ]);
});

it('keeps the buyer total equal to seller plus all commission parts (TRX-T16)', function (int $price, int $pct, int $feePct) {
    $breakdown = PurchaseBreakdown::calculate($price, $pct, $feePct);

    expect($breakdown['seller'] + $breakdown['inspector'] + $breakdown['platform'])->toBe($price)
        ->and($breakdown['commission'])->toBe($breakdown['inspector'] + $breakdown['platform']);
})->with([
    [100000000, 3, 10],
    [75500000, 3, 10],
    [15000000, 5, 10],
    [10000001, 3, 10],
]);

it('returns zero commission parts when the commission pct is zero', function () {
    $breakdown = PurchaseBreakdown::calculate(100000000, 0, 10);

    expect($breakdown)->toBe([
        'commission' => 0,
        'platform' => 0,
        'inspector' => 0,
        'seller' => 100000000,
    ]);
});
