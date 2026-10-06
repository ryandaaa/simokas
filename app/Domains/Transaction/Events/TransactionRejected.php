<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class TransactionRejected
{
    use Dispatchable;

    public function __construct(
        public readonly string $transactionId,
        public readonly string $listingId,
        public readonly string $buyerId,
        public readonly string $sellerId,
    ) {}
}
