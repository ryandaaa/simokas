<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;

/**
 * Dispatcher action untuk pembatalan job oleh pemohon atau admin (JOB-3).
 *
 * Mengarahkan pemanggilan ke CancelJobByAdmin bila aktor adalah admin,
 * atau ke CancelJobByRequester bila aktor adalah pemohon.
 */
final class CancelJob
{
    public function __construct(
        private readonly CancelJobByRequester $cancelByRequester,
        private readonly CancelJobByAdmin $cancelByAdmin,
    ) {}

    public function handle(User $actor, InspectionJob $job, ?string $reason = null, ?bool $refund = null): InspectionJob
    {
        if ($actor->isAdmin()) {
            return $this->cancelByAdmin->handle($actor, $job, $reason, $refund ?? false);
        }

        return $this->cancelByRequester->handle($actor, $job, $reason);
    }
}
