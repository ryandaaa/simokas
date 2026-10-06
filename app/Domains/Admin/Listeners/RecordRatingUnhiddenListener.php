<?php

declare(strict_types=1);

namespace App\Domains\Admin\Listeners;

use App\Domains\Admin\Actions\RecordAuditLog;
use App\Domains\Rating\Events\RatingUnhidden;
use App\Domains\Rating\Models\Rating;

final class RecordRatingUnhiddenListener
{
    public function __construct(
        private readonly RecordAuditLog $recordAuditLog,
    ) {}

    public function handle(RatingUnhidden $event): void
    {
        $rating = Rating::query()->find($event->ratingId);

        $this->recordAuditLog->handle(
            actor: auth()->user(),
            action: 'unhide_rating',
            entity: 'rating',
            entityId: $event->ratingId,
            meta: [
                'inspector_id' => $rating?->inspector_id,
                'transaction_id' => $rating?->transaction_id,
            ],
        );
    }
}
