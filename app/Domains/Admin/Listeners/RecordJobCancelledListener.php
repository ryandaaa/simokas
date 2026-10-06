<?php

declare(strict_types=1);

namespace App\Domains\Admin\Listeners;

use App\Domains\Admin\Actions\RecordAuditLog;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Events\JobCancelled;

final class RecordJobCancelledListener
{
    public function __construct(
        private readonly RecordAuditLog $recordAuditLog,
    ) {}

    public function handle(JobCancelled $event): void
    {
        if ($event->cancelledBy === null) {
            return;
        }

        /** @var User|null $user */
        $user = User::query()->find($event->cancelledBy);

        if ($user === null || ! $user->isAdmin()) {
            return;
        }

        $this->recordAuditLog->handle(
            actor: $user,
            action: 'cancel_job',
            entity: 'job',
            entityId: $event->jobId,
            meta: [
                'listing_id' => $event->listingId,
                'reason' => $event->cancelReason,
            ],
        );
    }
}
