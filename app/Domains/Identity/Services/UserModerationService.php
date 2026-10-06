<?php

declare(strict_types=1);

namespace App\Domains\Identity\Services;

use App\Domains\Identity\Actions\Moderation\ActivateUser;
use App\Domains\Identity\Actions\Moderation\BlockUser;
use App\Domains\Identity\Contracts\UserModerationContract;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Contracts\InspectionJobModerationContract;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Transaction\Contracts\TransactionModerationContract;
use App\Domains\Transaction\Models\Transaction;

final class UserModerationService implements UserModerationContract
{
    public function __construct(
        private readonly BlockUser $blockUser,
        private readonly ActivateUser $activateUser,
        private readonly InspectionJobModerationContract $jobModeration,
        private readonly TransactionModerationContract $transactionModeration,
    ) {}

    public function blockUser(User $admin, string $userId, ?string $reason, bool $cancelActiveItems): void
    {
        $this->blockUser->handle($admin, $userId, $reason, $cancelActiveItems);
    }

    public function activateUser(User $admin, string $userId): void
    {
        $this->activateUser->handle($admin, $userId);
    }

    public function activeItemsForUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $result = [];
        foreach ($userIds as $userId) {
            $result[$userId] = [];
        }

        /** @var InspectionJob $job */
        foreach ($this->jobModeration->activeJobsForUsers($userIds) as $job) {
            foreach ($this->userIdsForJob($job, $userIds) as $userId) {
                $result[$userId][] = ['type' => 'job', 'id' => (string) $job->getKey()];
            }
        }

        /** @var Transaction $transaction */
        foreach ($this->transactionModeration->activeTransactionsForUsers($userIds) as $transaction) {
            foreach ($this->userIdsForTransaction($transaction, $userIds) as $userId) {
                $result[$userId][] = ['type' => 'transaction', 'id' => (string) $transaction->getKey()];
            }
        }

        return $result;
    }

    /**
     * @param  list<string>  $userIds
     * @return list<string>
     */
    private function userIdsForJob(InspectionJob $job, array $userIds): array
    {
        $involved = [(string) $job->requester_id, (string) $job->inspector_id];

        return array_values(array_intersect($userIds, array_filter($involved)));
    }

    /**
     * @param  list<string>  $userIds
     * @return list<string>
     */
    private function userIdsForTransaction(Transaction $transaction, array $userIds): array
    {
        $involved = [
            (string) $transaction->buyer_id,
            (string) $transaction->listing->seller_id,
        ];

        return array_values(array_intersect($userIds, array_filter($involved)));
    }
}
