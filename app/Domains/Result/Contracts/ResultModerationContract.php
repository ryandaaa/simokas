<?php

declare(strict_types=1);

namespace App\Domains\Result\Contracts;

use App\Domains\Identity\Models\User;
use App\Domains\Result\Models\InspectionResult;

/**
 * Contract for admin moderation of inspection results.
 */
interface ResultModerationContract
{
    /**
     * Reopen locked inspection result back to draft.
     */
    public function reopen(User $admin, InspectionResult $result, string $reason): InspectionResult;
}
