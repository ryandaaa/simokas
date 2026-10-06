<?php

declare(strict_types=1);

namespace App\Domains\Result\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class ResultSubmitted
{
    use Dispatchable;

    public function __construct(
        public readonly string $resultId,
        public readonly string $jobId,
        public readonly string $inspectorId,
        public readonly string $listingId,
    ) {}
}
