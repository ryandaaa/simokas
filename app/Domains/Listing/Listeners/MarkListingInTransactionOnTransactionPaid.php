<?php

declare(strict_types=1);

namespace App\Domains\Listing\Listeners;

use App\Domains\Listing\Actions\MarkListingInTransaction;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Events\TransactionPaid;

/**
 * Reaksi milik Listing atas event publik Transaction (TRX-2, perubahan shared).
 *
 * Aturan A2/A3: Transaction tidak memanggil Action Listing langsung; Listing
 * bereaksi pada event TransactionPaid dengan memanggil Action miliknya sendiri.
 */
final class MarkListingInTransactionOnTransactionPaid
{
    public function __construct(private readonly MarkListingInTransaction $markInTransaction) {}

    public function handle(TransactionPaid $event): void
    {
        $listing = Listing::query()->find($event->listingId);

        if (! $listing instanceof Listing) {
            return;
        }

        ($this->markInTransaction)->handle($listing);
    }
}
