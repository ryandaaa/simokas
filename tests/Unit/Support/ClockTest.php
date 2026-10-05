<?php

declare(strict_types=1);

use App\Support\Clock;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    Carbon::setTestNow(null);
});

it('returns current time in UTC reference by default', function (): void {
    Carbon::setTestNow('2026-10-05 07:30:00 UTC');

    $now = Clock::now();

    expect($now->timezoneName)->toBe('UTC')
        ->and($now->format('Y-m-d H:i:s'))->toBe('2026-10-05 07:30:00');
});

it('respects frozen time via travelTo', function (): void {
    $this->travelTo(Carbon::parse('2026-10-05 07:30:00', 'UTC'));

    expect(Clock::now()->format('Y-m-d H:i:s'))->toBe('2026-10-05 07:30:00');
});

it('converts current UTC time to WIB timezone', function (): void {
    Carbon::setTestNow('2026-10-05 07:30:00 UTC');

    $wib = Clock::toWib();

    expect($wib->timezoneName)->toBe('Asia/Jakarta')
        ->and($wib->format('Y-m-d H:i:s'))->toBe('2026-10-05 14:30:00');
});

it('converts given datetime or string to WIB', function (): void {
    $utcString = '2026-10-05 07:30:00';
    $wib = Clock::toWib($utcString);

    expect($wib->timezoneName)->toBe('Asia/Jakarta')
        ->and($wib->format('Y-m-d H:i:s'))->toBe('2026-10-05 14:30:00');

    $dateTime = new DateTimeImmutable('2026-10-05 07:30:00', new DateTimeZone('UTC'));
    expect(Clock::toWib($dateTime)->format('H.i'))->toBe('14.30');
});

it('formats WIB with short Indonesian month name and dot time separator', function (): void {
    Carbon::setTestNow('2026-10-05 07:30:00 UTC');

    expect(Clock::formatWib())->toBe('5 Okt 2026, 14.30 WIB');
});

it('formats explicit date instances or strings into WIB format', function (): void {
    $formatted = Clock::formatWib('2026-10-05 07:30:00');
    expect($formatted)->toBe('5 Okt 2026, 14.30 WIB');

    $carbon = Carbon::parse('2026-10-05 14:30:00', 'Asia/Jakarta');
    expect(Clock::formatWib($carbon))->toBe('5 Okt 2026, 14.30 WIB');
});

it('formats all 12 months in Indonesian short names correctly', function (int $month, string $expectedShortMonth): void {
    $date = Carbon::create(2026, $month, 5, 14, 30, 0, 'Asia/Jakarta');

    expect(Clock::formatWib($date))->toBe("5 {$expectedShortMonth} 2026, 14.30 WIB");
})->with([
    'January' => [1, 'Jan'],
    'February' => [2, 'Feb'],
    'March' => [3, 'Mar'],
    'April' => [4, 'Apr'],
    'May' => [5, 'Mei'],
    'June' => [6, 'Jun'],
    'July' => [7, 'Jul'],
    'August' => [8, 'Agt'],
    'September' => [9, 'Sep'],
    'October' => [10, 'Okt'],
    'November' => [11, 'Nov'],
    'December' => [12, 'Des'],
]);

it('formats morning single-digit hour with leading zero and dot time', function (): void {
    $date = Carbon::parse('2026-10-05 02:05:00', 'UTC'); // 09:05 WIB

    expect(Clock::formatWib($date))->toBe('5 Okt 2026, 09.05 WIB');
});

it('provides timezone constants', function (): void {
    expect(Clock::TIMEZONE)->toBe('UTC')
        ->and(Clock::DISPLAY_TIMEZONE)->toBe('Asia/Jakarta');
});
