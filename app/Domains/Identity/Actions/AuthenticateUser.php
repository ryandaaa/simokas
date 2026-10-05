<?php

declare(strict_types=1);

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthenticateUser
{
    /**
     * @throws ValidationException
     */
    public function handle(string $email, string $password, bool $remember = false): User
    {
        $user = User::query()
            ->where('email', strtolower(trim($email)))
            ->first();

        // AUTH-R6 / AUTH-T07: Generic error message, never leak whether email or password is wrong
        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('identity.login.failed'),
            ]);
        }

        // AUTH-T09 / AUTH-E4: Blocked user cannot log in
        if ($user->status === UserStatus::Blocked) {
            throw ValidationException::withMessages([
                'email' => __('identity.login.blocked_message'),
            ]);
        }

        Auth::login($user, $remember);

        if (session()->isStarted()) {
            session()->regenerate();
        }

        return $user;
    }
}
