<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Contracts;

use App\Domains\Identity\Models\User;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for admin moderation of transactions (ADM-4).
 */
interface TransactionModerationContract
{
    /**
     * Cancel a transaction by an active admin (offered, accepted, paid_held).
     * Refund 100% only on paid_held, per CancelTransaction rules.
     */
    public function cancelByAdmin(User $admin, string $transactionId, string $reason): void;

    /**
     * Active transactions (offered, accepted, paid_held) where any of the
     * given users is the buyer or the seller (listing owner).
     *
     * @param  list<string>  $userIds
     * @return Collection<int, Transaction>
     */
    public function activeTransactionsForUsers(array $userIds): Collection;
}
