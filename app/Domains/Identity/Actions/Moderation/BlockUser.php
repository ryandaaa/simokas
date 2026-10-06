<?php

declare(strict_types=1);

namespace App\Domains\Identity\Actions\Moderation;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Events\UserBlocked;
use App\Domains\Identity\Exceptions\CannotBlockSelfException;
use App\Domains\Identity\Exceptions\UserHasActiveItemsException;
use App\Domains\Identity\Exceptions\UserModerationException;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Contracts\InspectionJobModerationContract;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Transaction\Contracts\TransactionModerationContract;
use App\Support\Clock;
use App\Support\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Block a user (ADM-4, ADM-R6, ADM-R9, ADM-E5).
 *
 * - active/pending -> blocked. Already blocked: idempotent no-op.
 * - An admin cannot block themselves.
 * - Blocked with active jobs/transactions: rejected with
 *   UserHasActiveItemsException (409) carrying the item list, unless
 *   $cancelActiveItems with a required reason — then every active item
 *   is cancelled first, atomically: one failure rolls everything back.
 * - The UserBlocked event fires after commit, exactly once, only on change.
 */
final class BlockUser
{
    public function __construct(
        private readonly InspectionJobModerationContract $jobModeration,
        private readonly TransactionModerationContract $transactionModeration,
    ) {}

    public function handle(User $admin, string $userId, ?string $reason, bool $cancelActiveItems): void
    {
        if (! Gate::forUser($admin)->allows('admin.access')) {
            throw UserModerationException::unauthorized();
        }

        if ((string) $admin->getKey() === $userId) {
            throw CannotBlockSelfException::create();
        }

        $trimmedReason = $reason !== null ? trim($reason) : null;

        if ($cancelActiveItems && ($trimmedReason === null || $trimmedReason === '')) {
            throw UserModerationException::reasonRequired();
        }

        $changed = DB::transaction(function () use ($admin, $userId, $trimmedReason, $cancelActiveItems): bool {
            /** @var User $user */
            $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

            $fromStatus = $user->status;

            // Idempotent: already blocked.
            if ($fromStatus === UserStatus::Blocked) {
                return false;
            }

            $jobs = $this->jobModeration->activeJobsForUsers([$userId]);
            $transactions = $this->transactionModeration->activeTransactionsForUsers([$userId]);

            $items = [];
            foreach ($jobs as $job) {
                $items[] = ['type' => 'job', 'id' => (string) $job->getKey(), 'job' => $job];
            }
            foreach ($transactions as $transaction) {
                $items[] = ['type' => 'transaction', 'id' => (string) $transaction->getKey(), 'transaction' => $transaction];
            }

            if ($items !== []) {
                if (! $cancelActiveItems) {
                    throw UserHasActiveItemsException::create(
                        array_map(fn (array $item): array => ['type' => $item['type'], 'id' => $item['id']], $items)
                    );
                }

                foreach ($items as $item) {
                    if ($item['type'] === 'job') {
                        /** @var InspectionJob $job */
                        $job = $item['job'];
                        $refund = in_array($job->status, [JobStatus::Paid, JobStatus::Taken, JobStatus::Scheduled], true);
                        $this->jobModeration->cancelJob($admin, $job, $trimmedReason, $refund);
                    } else {
                        $this->transactionModeration->cancelByAdmin($admin, $item['id'], (string) $trimmedReason);
                    }
                }
            }

            $affected = User::query()
                ->whereKey($userId)
                ->where('status', $fromStatus->value)
                ->update([
                    'status' => UserStatus::Blocked->value,
                    'updated_at' => Clock::now(),
                ]);

            if ($affected === 0) {
                /** @var User $current */
                $current = User::query()->findOrFail($userId);

                // Idempotent: blocked by a concurrent admin.
                if ($current->status === UserStatus::Blocked) {
                    return false;
                }

                // Unreachable while the row lock is held; fail loudly.
                throw new DomainException(
                    __('identity.errors.moderation_failed'),
                    DomainException::CODE_CONFLICT,
                );
            }

            return true;
        });

        if ($changed) {
            UserBlocked::dispatch(
                userId: $userId,
                blockedBy: (string) $admin->getKey(),
                reason: $trimmedReason !== '' ? $trimmedReason : null,
            );
        }
    }
}
