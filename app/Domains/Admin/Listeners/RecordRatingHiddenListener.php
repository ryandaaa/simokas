<?php

declare(strict_types=1);

namespace App\Domains\Admin\Listeners;

use App\Domains\Admin\Actions\RecordAuditLog;
use App\Domains\Rating\Events\RatingHidden;
use App\Domains\Rating\Models\Rating;

final class RecordRatingHiddenListener
{
    public function __construct(
        private readonly RecordAuditLog $recordAuditLog,
    ) {}

    public function handle(RatingHidden $event): void
    {
        $rating = Rating::query()->find($event->ratingId);

        $this->recordAuditLog->handle(
            actor: auth()->user(),
            action: 'hide_rating',
            entity: 'rating',
            entityId: $event->ratingId,
            meta: [
                'inspector_id' => $rating?->inspector_id,
                'transaction_id' => $rating?->transaction_id,
            ],
        );
    }
}
