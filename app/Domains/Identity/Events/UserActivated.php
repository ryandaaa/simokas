<?php

declare(strict_types=1);

namespace App\Domains\Identity\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class UserActivated
{
    use Dispatchable;

    public function __construct(
        public readonly string $userId,
        public readonly string $activatedBy,
    ) {}
}
