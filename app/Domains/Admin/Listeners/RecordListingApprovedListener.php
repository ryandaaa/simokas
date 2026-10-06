<?php

declare(strict_types=1);

namespace App\Domains\Admin\Listeners;

use App\Domains\Admin\Actions\RecordAuditLog;
use App\Domains\Listing\Events\ListingApproved;

final class RecordListingApprovedListener
{
    public function __construct(
        private readonly RecordAuditLog $recordAuditLog,
    ) {}

    public function handle(ListingApproved $event): void
    {
        $this->recordAuditLog->handle(
            actor: $event->approvedBy,
            action: 'approve_listing',
            entity: 'listing',
            entityId: $event->listingId,
            meta: [
                'seller_id' => $event->sellerId,
            ],
        );
    }
}
