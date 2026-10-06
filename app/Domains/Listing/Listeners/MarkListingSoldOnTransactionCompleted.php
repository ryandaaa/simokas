<?php

declare(strict_types=1);

namespace App\Domains\Listing\Listeners;

use App\Domains\Listing\Actions\MarkListingSold;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Events\TransactionCompleted;

/**
 * Reaksi milik Listing atas event publik Transaction (TRX-2, perubahan shared).
 *
 * Aturan A2/A3: Transaction tidak memanggil Action Listing langsung; Listing
 * bereaksi pada event TransactionCompleted dengan memanggil Action miliknya sendiri.
 */
final class MarkListingSoldOnTransactionCompleted
{
    public function __construct(private readonly MarkListingSold $markSold) {}

    public function handle(TransactionCompleted $event): void
    {
        $listing = Listing::query()->find($event->listingId);

        if (! $listing instanceof Listing) {
            return;
        }

        ($this->markSold)->handle($listing);
    }
}
