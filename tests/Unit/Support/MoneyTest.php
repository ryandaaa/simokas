<?php

declare(strict_types=1);

use App\Support\Exceptions\DomainException;
use App\Support\Money;

it('PAY-T02 Money::split sums to initial amount for multiple values', function (int $amount, int $pct, int $expectedPlatform, int $expectedRecipient): void {
    $split = Money::split($amount, $pct);

    expect($split)->toHaveKeys(['platform', 'recipient'])
        ->and($split['platform'])->toBe($expectedPlatform)
        ->and($split['recipient'])->toBe($expectedRecipient)
        ->and($split['platform'] + $split['recipient'])->toBe($amount);
})->with([
    // Issue & doc explicit examples
    '15.005 at 10% (issue example)' => [15005, 10, 1501, 13504],
    'car inspection 50.000 at 10%' => [50000, 10, 5000, 45000],
    'motorcycle access 15.000 at 10%' => [15000, 10, 1500, 13500],
    'transaction commission 3.000.000 at 10%' => [3000000, 10, 300000, 2700000],

    // Rounding half-up edge cases
    '1 at 50% rounds up to 1' => [1, 50, 1, 0],
    '1 at 49% rounds down to 0' => [1, 49, 0, 1],
    '3 at 50% rounds up (1.5 -> 2)' => [3, 50, 2, 1],
    '5 at 10% rounds up (0.5 -> 1)' => [5, 10, 1, 4],
    '4 at 10% rounds down (0.4 -> 0)' => [4, 10, 0, 4],
    '15 at 10% rounds up (1.5 -> 2)' => [15, 10, 2, 13],
    '25 at 10% rounds up (2.5 -> 3)' => [25, 10, 3, 22],
    '35 at 10% rounds up (3.5 -> 4)' => [35, 10, 4, 31],

    // Percentage boundary values
    'zero amount at 10%' => [0, 10, 0, 0],
    'zero amount at 0%' => [0, 0, 0, 0],
    'zero amount at 100%' => [0, 100, 0, 0],
    '50.000 at 0% (platform 0, all to recipient)' => [50000, 0, 0, 50000],
    '50.000 at 100% (all to platform, recipient 0)' => [50000, 100, 50000, 0],

    // Odd and non-round amounts across various percentages
    '7.777 at 3%' => [7777, 3, 233, 7544], // 7777 * 3 = 23331, 233.31 -> 233
    '7.777 at 7%' => [7777, 7, 544, 7233], // 7777 * 7 = 54439, 544.39 -> 544
    '99.999 at 15%' => [99999, 15, 15000, 84999], // 99999 * 15 = 1499985, 14999.85 -> 15000
    '123.456 at 33%' => [123456, 33, 40740, 82716], // 123456 * 33 = 4074048, 40740.48 -> 40740
    '500.001 at 67%' => [500001, 67, 335001, 165000], // 500001 * 67 = 33500067, 335000.67 -> 335001
    '1.000.000.000 at 10%' => [1000000000, 10, 100000000, 900000000],
]);

it('PAY-T02 invariant: Money::split always preserves total amount across dense range', function (): void {
    // Test 100 consecutive numbers around rounding boundaries for multiple percentages
    $percentages = [1, 3, 7, 10, 15, 20, 25, 33, 50, 75, 90, 99];

    foreach ($percentages as $pct) {
        for ($amount = 14950; $amount <= 15050; $amount++) {
            $split = Money::split($amount, $pct);

            expect($split['platform'] + $split['recipient'])->toBe($amount)
                ->and($split['platform'])->toBeGreaterThanOrEqual(0)
                ->and($split['recipient'])->toBeGreaterThanOrEqual(0);
        }
    }
});

it('rejects negative amount with domain exception', function (): void {
    expect(fn () => Money::split(-1, 10))->toThrow(DomainException::class);
});

it('rejects percentage below 0 with domain exception', function (): void {
    expect(fn () => Money::split(50000, -1))->toThrow(DomainException::class);
});

it('rejects percentage above 100 with domain exception', function (): void {
    expect(fn () => Money::split(50000, 101))->toThrow(DomainException::class);
});

it('formats money without decimals and with dot thousand separator', function (int $amount, string $expected): void {
    expect(Money::format($amount))->toBe($expected);
})->with([
    'zero' => [0, 'Rp0'],
    'standard 50.000' => [50000, 'Rp50.000'],
    'odd 15.005' => [15005, 'Rp15.005'],
    'one million' => [1000000, 'Rp1.000.000'],
    'large amount' => [123456789, 'Rp123.456.789'],
    'negative amount' => [-50000, '-Rp50.000'],
]);
