<?php

declare(strict_types=1);

namespace App\Domains\Identity\Actions\Moderation;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Events\UserActivated;
use App\Domains\Identity\Exceptions\UserModerationException;
use App\Domains\Identity\Models\User;
use App\Support\Clock;
use App\Support\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Activate a blocked user (ADM-4).
 *
 * - blocked -> active. Already active: idempotent no-op.
 * - Activating a pending user is not allowed here (that flow belongs to
 *   inspector approval); it throws.
 * - The UserActivated event fires after commit, exactly once, only on change.
 * - Listings stay inactive: the seller reactivates them via the existing action.
 */
final class ActivateUser
{
    public function handle(User $admin, string $userId): void
    {
        if (! Gate::forUser($admin)->allows('admin.access')) {
            throw UserModerationException::unauthorized();
        }

        $changed = DB::transaction(function () use ($userId): bool {
            /** @var User $user */
            $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

            // Idempotent: already active.
            if ($user->status === UserStatus::Active) {
                return false;
            }

            if ($user->status !== UserStatus::Blocked) {
                throw UserModerationException::notBlocked();
            }

            $affected = User::query()
                ->whereKey($userId)
                ->where('status', UserStatus::Blocked->value)
                ->update([
                    'status' => UserStatus::Active->value,
                    'updated_at' => Clock::now(),
                ]);

            if ($affected === 0) {
                /** @var User $current */
                $current = User::query()->findOrFail($userId);

                if ($current->status === UserStatus::Active) {
                    return false;
                }

                throw new DomainException(
                    __('identity.errors.moderation_failed'),
                    DomainException::CODE_CONFLICT,
                );
            }

            return true;
        });

        if ($changed) {
            UserActivated::dispatch(
                userId: $userId,
                activatedBy: (string) $admin->getKey(),
            );
        }
    }
}
