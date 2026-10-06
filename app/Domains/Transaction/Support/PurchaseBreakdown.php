<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Support;

use App\Support\Money;

/**
 * The single location of the K2 purchase commission formula.
 *
 * K2: the sale commission is `commission_pct` of the price (snapshot on the
 * transaction at offer time); the platform takes `platform_fee_pct` of that
 * commission (setting snapshot at pay time); the inspector gets the rest;
 * the seller gets the price minus the commission. All integer rupiah.
 *
 * Everyone — the paid listener (snapshot), the detail page (display), and
 * tests — must go through here so the numbers can never drift apart.
 */
final class PurchaseBreakdown
{
    /**
     * @return array{commission: int, platform: int, inspector: int, seller: int}
     */
    public static function calculate(int $priceAmount, int $commissionPct, int $platformFeePct): array
    {
        $sale = Money::split($priceAmount, $commissionPct);
        $fee = Money::split($sale['platform'], $platformFeePct);

        return [
            'commission' => $sale['platform'],
            'platform' => $fee['platform'],
            'inspector' => $fee['recipient'],
            'seller' => $sale['recipient'],
        ];
    }
}
