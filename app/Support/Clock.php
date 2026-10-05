<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

final class Clock
{
    public const string TIMEZONE = 'UTC';

    public const string DISPLAY_TIMEZONE = 'Asia/Jakarta';

    /**
     * Get the current time in the server UTC reference.
     */
    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    /**
     * Convert any date, datetime string, or now into a Carbon instance in WIB (Asia/Jakarta).
     */
    public static function toWib(DateTimeInterface|string|null $date = null): Carbon
    {
        if ($date === null) {
            return self::now()->setTimezone(self::DISPLAY_TIMEZONE);
        }

        if (is_string($date)) {
            $carbon = Carbon::parse($date, self::TIMEZONE);
        } else {
            $carbon = Carbon::instance($date);
        }

        return $carbon->setTimezone(self::DISPLAY_TIMEZONE);
    }

    /**
     * Format a date into WIB human display: "5 Okt 2026, 14.30 WIB".
     * Uses short Indonesian month names and dot separator for hours and minutes.
     */
    public static function formatWib(DateTimeInterface|string|null $date = null): string
    {
        $wib = self::toWib($date);

        return $wib->locale('id')->translatedFormat('j M Y, H.i').' WIB';
    }
}
