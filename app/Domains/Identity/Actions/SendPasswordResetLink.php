<?php

declare(strict_types=1);

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\Password;

final class SendPasswordResetLink
{
    public function handle(string $email): string
    {
        $user = User::query()
            ->where('email', strtolower(trim($email)))
            ->first();

        // If user exists and is not blocked, generate token and send notification
        if ($user && ! $user->isBlocked()) {
            $token = Password::broker()->createToken($user);
            $user->sendPasswordResetNotification($token);
        }

        // AUTH-R6: Response is ALWAYS the same whether email is registered or not
        return Password::RESET_LINK_SENT;
    }
}
