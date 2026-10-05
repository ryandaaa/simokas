<?php

declare(strict_types=1);

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class ActivateUser
{
    public function handle(string $userId): User
    {
        return DB::transaction(function () use ($userId): User {
            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($userId);

            if ($user->status === UserStatus::Pending) {
                $user->update(['status' => UserStatus::Active]);
            }

            return $user;
        });
    }
}
