<?php

declare(strict_types=1);

namespace App\Domains\Admin\Listeners;

use App\Domains\Admin\Actions\RecordAuditLog;
use App\Domains\Result\Events\ResultReopened;

final class RecordResultReopenedListener
{
    public function __construct(
        private readonly RecordAuditLog $recordAuditLog,
    ) {}

    public function handle(ResultReopened $event): void
    {
        $this->recordAuditLog->handle(
            actor: $event->reopenedBy,
            action: 'reopen_result',
            entity: 'inspection_result',
            entityId: $event->resultId,
            meta: [
                'job_id' => $event->jobId,
                'inspector_id' => $event->inspectorId,
                'reason' => $event->reason,
            ],
        );
    }
}
