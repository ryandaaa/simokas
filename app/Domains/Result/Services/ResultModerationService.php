<?php

declare(strict_types=1);

namespace App\Domains\Result\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Result\Actions\ReopenInspectionResult;
use App\Domains\Result\Contracts\ResultModerationContract;
use App\Domains\Result\Models\InspectionResult;

final class ResultModerationService implements ResultModerationContract
{
    public function __construct(
        private readonly ReopenInspectionResult $reopenInspectionResult,
    ) {}

    public function reopen(User $admin, InspectionResult $result, string $reason): InspectionResult
    {
        return $this->reopenInspectionResult->handle($admin, $result, $reason);
    }
}
