<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class InspectorApproved
{
    use Dispatchable;

    public function __construct(
        public readonly string $inspectorProfileId,
        public readonly string $userId,
        public readonly string $verifiedBy,
    ) {}
}
