<?php

declare(strict_types=1);

namespace App\Domains\Admin\Listeners;

use App\Domains\Admin\Actions\RecordAuditLog;
use App\Domains\Listing\Events\ListingRejected;

final class RecordListingRejectedListener
{
    public function __construct(
        private readonly RecordAuditLog $recordAuditLog,
    ) {}

    public function handle(ListingRejected $event): void
    {
        $this->recordAuditLog->handle(
            actor: $event->rejectedBy,
            action: 'reject_listing',
            entity: 'listing',
            entityId: $event->listingId,
            meta: [
                'seller_id' => $event->sellerId,
                'reason' => $event->reason,
            ],
        );
    }
}
