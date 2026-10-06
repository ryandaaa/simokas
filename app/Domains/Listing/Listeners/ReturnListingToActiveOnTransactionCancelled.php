<?php

declare(strict_types=1);

namespace App\Domains\Listing\Listeners;

use App\Domains\Listing\Actions\ReturnListingFromTransaction;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Events\TransactionCancelled;

/**
 * Reaksi milik Listing atas event publik Transaction (TRX-3, perubahan shared).
 *
 * Aturan A2/A3: Transaction tidak memanggil Action Listing langsung; Listing
 * bereaksi pada event TransactionCancelled dengan memanggil Action miliknya
 * sendiri (ReturnListingFromTransaction).
 */
final class ReturnListingToActiveOnTransactionCancelled
{
    public function __construct(private readonly ReturnListingFromTransaction $returnFromTransaction) {}

    public function handle(TransactionCancelled $event): void
    {
        $listing = Listing::query()->find($event->listingId);

        if (! $listing instanceof Listing) {
            return;
        }

        ($this->returnFromTransaction)->handle($listing);
    }
}
