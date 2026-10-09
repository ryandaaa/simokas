<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\DTOs;

use App\Domains\Payment\Enums\LedgerEntryType;

final readonly class EarningsSlice
{
    public function __construct(
        public LedgerEntryType $type,
        public int $amount,
        public float $percent,
    ) {}
}
