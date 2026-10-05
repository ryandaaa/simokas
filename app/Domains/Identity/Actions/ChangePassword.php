<?php

declare(strict_types=1);

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class ChangePassword
{
    /**
     * AUTH-T13 / AUTH-E10: Ganti kata sandi pengguna dengan verifikasi kata sandi saat ini.
     * Sesi perangkat lain tetap, sesi perangkat saat ini dipertahankan dan diregenerasi.
     * Field role, status, email dari input diabaikan.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(User $user, string $currentPassword, string $newPassword, array $data = []): User
    {
        Gate::authorize('changePassword', $user);

        return DB::transaction(function () use ($user, $currentPassword, $newPassword) {
            if (! Hash::check($currentPassword, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => __('identity.account.current_password_incorrect'),
                ]);
            }

            $user->password = Hash::make($newPassword);
            $user->save();

            if (session()->isStarted()) {
                session()->regenerate();
            }

            return $user;
        });
    }
}
