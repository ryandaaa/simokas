<?php

declare(strict_types=1);

namespace App\Domains\Payment\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Models\Payment;

final class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->payer_id || $user->isAdmin();
    }

    public function pay(User $user, Payment $payment): bool
    {
        return $user->id === $payment->payer_id;
    }
}
