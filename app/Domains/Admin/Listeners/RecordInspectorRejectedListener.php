<?php

declare(strict_types=1);

namespace App\Domains\Admin\Listeners;

use App\Domains\Admin\Actions\RecordAuditLog;
use App\Domains\Inspector\Events\InspectorRejected;

final class RecordInspectorRejectedListener
{
    public function __construct(
        private readonly RecordAuditLog $recordAuditLog,
    ) {}

    public function handle(InspectorRejected $event): void
    {
        $this->recordAuditLog->handle(
            actor: $event->rejectedBy,
            action: 'reject_inspector',
            entity: 'inspector_profile',
            entityId: $event->inspectorProfileId,
            meta: [
                'user_id' => $event->userId,
                'reason' => $event->reason,
            ],
        );
    }
}
