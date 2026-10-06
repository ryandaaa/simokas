<?php

declare(strict_types=1);

namespace App\Domains\Admin\Actions;

use App\Domains\Admin\Models\AuditLog;
use App\Domains\Identity\Models\User;
use App\Support\Clock;

/**
 * Action to record sensitive admin actions (PRD 18.18, ADM-R8, ADM-T13).
 */
final class RecordAuditLog
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function handle(
        User|string|null $actor,
        string $action,
        string $entity,
        ?string $entityId = null,
        ?array $meta = null,
    ): AuditLog {
        $actorId = $actor instanceof User ? $actor->id : $actor;

        return AuditLog::create([
            'actor_id' => $actorId,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'meta' => $meta,
            'created_at' => Clock::now(),
            'updated_at' => Clock::now(),
        ]);
    }
}
