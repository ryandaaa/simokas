<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class JobStarted
{
    use Dispatchable;

    public function __construct(
        public readonly string $jobId,
        public readonly string $listingId,
        public readonly string $inspectorId,
    ) {}
}
