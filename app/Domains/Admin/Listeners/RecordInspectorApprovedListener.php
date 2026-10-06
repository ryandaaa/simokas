<?php

declare(strict_types=1);

namespace App\Domains\Admin\Listeners;

use App\Domains\Admin\Actions\RecordAuditLog;
use App\Domains\Inspector\Events\InspectorApproved;

final class RecordInspectorApprovedListener
{
    public function __construct(
        private readonly RecordAuditLog $recordAuditLog,
    ) {}

    public function handle(InspectorApproved $event): void
    {
        $this->recordAuditLog->handle(
            actor: $event->verifiedBy,
            action: 'approve_inspector',
            entity: 'inspector_profile',
            entityId: $event->inspectorProfileId,
            meta: [
                'user_id' => $event->userId,
            ],
        );
    }
}
