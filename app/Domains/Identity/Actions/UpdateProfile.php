<?php

declare(strict_types=1);

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class UpdateProfile
{
    /**
     * AUTH-T13: Perbarui nama dan nomor HP pengguna.
     * Field role, status, email, dan id dari input diabaikan.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): User
    {
        Gate::authorize('updateProfile', $user);

        return DB::transaction(function () use ($user, $data) {
            $user->name = trim((string) $data['name']);
            $user->phone = trim((string) $data['phone']);
            $user->save();

            return $user;
        });
    }
}
