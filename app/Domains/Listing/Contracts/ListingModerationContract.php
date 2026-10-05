<?php

declare(strict_types=1);

namespace App\Domains\Listing\Contracts;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\DTOs\ListingModerationDetail;
use App\Domains\Listing\DTOs\ListingModerationQueueItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ListingModerationContract
{
    /**
     * Pending review queue, oldest first.
     *
     * @return LengthAwarePaginator<int, ListingModerationQueueItem>
     */
    public function queue(int $perPage = 12): LengthAwarePaginator;

    public function detail(string $listingId): ListingModerationDetail;

    public function approve(User $admin, string $listingId): ListingModerationDetail;

    public function reject(User $admin, string $listingId, string $reason): ListingModerationDetail;
}
