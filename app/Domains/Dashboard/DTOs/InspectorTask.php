<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\DTOs;

use App\Domains\Inspection\Enums\JobStatus;
use Carbon\CarbonImmutable;

final readonly class InspectorTask
{
    /**
     * @param  array<string, mixed>  $actionParams
     */
    public function __construct(
        public string $jobId,
        public string $listingTitle,
        public ?string $city,
        public ?CarbonImmutable $scheduledAt,
        public JobStatus $status,
        public string $actionRoute,
        public array $actionParams,
    ) {}
}
