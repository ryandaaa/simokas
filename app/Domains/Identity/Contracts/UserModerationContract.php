<?php

declare(strict_types=1);

namespace App\Domains\Identity\Contracts;

use App\Domains\Identity\Models\User;

/**
 * Contract for admin moderation of users (ADM-4).
 *
 * Implemented in the Identity domain; the Admin domain only calls this.
 */
interface UserModerationContract
{
    /**
     * Block a user (active/pending -> blocked).
     *
     * Idempotent: blocking twice applies once. Throws
     * UserHasActiveItemsException (409) when the user has active jobs or
     * transactions unless $cancelActiveItems is true with a reason, in
     * which case the items are cancelled first, atomically.
     */
    public function blockUser(User $admin, string $userId, ?string $reason, bool $cancelActiveItems): void;

    /**
     * Activate a blocked user (blocked -> active). Idempotent.
     */
    public function activateUser(User $admin, string $userId): void;

    /**
     * Active items (jobs and transactions) for the given users, keyed by
     * user id. One batched read for admin listings (no N+1).
     *
     * @param  list<string>  $userIds
     * @return array<string, list<array{type: string, id: string}>>
     */
    public function activeItemsForUsers(array $userIds): array;
}
