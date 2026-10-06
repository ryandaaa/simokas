<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Action for seller to reject a purchase offer (TRX-1).
 * Seller can only reject their own listing's offer.
 */
final class RejectOffer
{
    public function handle(Transaction $transaction): Transaction
    {
        return DB::transaction(function () use ($transaction): Transaction {
            // Ensure offer is still in offered state
            if ($transaction->status !== TransactionStatus::Offered) {
                return $transaction; // Idempotent: return as-is if not in right state
            }

            $transaction->update([
                'status' => TransactionStatus::Rejected,
            ]);

            return $transaction->refresh();
        });
    }
}
