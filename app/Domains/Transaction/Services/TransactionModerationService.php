<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Transaction\Actions\CancelTransaction;
use App\Domains\Transaction\Contracts\TransactionModerationContract;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

final class TransactionModerationService implements TransactionModerationContract
{
    public function __construct(private readonly CancelTransaction $cancelTransaction) {}

    public function cancelByAdmin(User $admin, string $transactionId, string $reason): void
    {
        /** @var Transaction $transaction */
        $transaction = Transaction::query()->findOrFail($transactionId);

        $this->cancelTransaction->handle($admin, $transaction, $reason);
    }

    public function activeTransactionsForUsers(array $userIds): Collection
    {
        if ($userIds === []) {
            /** @var Collection<int, Transaction> $empty */
            $empty = new Collection;

            return $empty;
        }

        /** @var Collection<int, Transaction> $transactions */
        $transactions = Transaction::query()
            ->with('listing:id,seller_id')
            ->whereIn('status', [
                TransactionStatus::Offered->value,
                TransactionStatus::Accepted->value,
                TransactionStatus::PaidHeld->value,
            ])
            ->where(function ($query) use ($userIds): void {
                $query->whereIn('buyer_id', $userIds)
                    ->orWhereHas('listing', function ($listingQuery) use ($userIds): void {
                        $listingQuery->whereIn('seller_id', $userIds);
                    });
            })
            ->get();

        return $transactions;
    }
}
