<?php

declare(strict_types=1);

namespace App\Support;

use App\Support\Exceptions\DomainException;

final class Money
{
    /**
     * Split an amount between platform and recipient using integer arithmetic.
     * Rounding half-up is applied to the platform share, remainder goes to recipient.
     *
     * @return array{platform: int, recipient: int}
     *
     * @throws DomainException
     */
    public static function split(int $amount, int $pct): array
    {
        if ($amount < 0) {
            throw new DomainException(
                message: 'Jumlah uang tidak boleh negatif.',
                errorCode: 'INVALID_AMOUNT',
                statusCode: 422,
            );
        }

        if ($pct < 0 || $pct > 100) {
            throw new DomainException(
                message: 'Persentase komisi harus berada di antara 0 dan 100.',
                errorCode: 'INVALID_PERCENTAGE',
                statusCode: 422,
            );
        }

        $platform = intdiv($amount * $pct + 50, 100);
        $recipient = $amount - $platform;

        return [
            'platform' => $platform,
            'recipient' => $recipient,
        ];
    }

    /**
     * Format an integer amount in Rupiah with thousand separator dots and no decimals.
     * Example: 50000 -> "Rp50.000"
     */
    public static function format(int $amount): string
    {
        if ($amount < 0) {
            return '-Rp'.number_format(abs($amount), 0, ',', '.');
        }

        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
