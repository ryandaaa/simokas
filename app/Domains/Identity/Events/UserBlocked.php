<?php

declare(strict_types=1);

namespace App\Domains\Identity\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class UserBlocked
{
    use Dispatchable;

    public function __construct(
        public readonly string $userId,
        public readonly string $blockedBy,
        public readonly ?string $reason = null,
    ) {}
}
