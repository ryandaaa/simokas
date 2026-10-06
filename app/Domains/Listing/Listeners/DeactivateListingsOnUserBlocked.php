<?php

declare(strict_types=1);

namespace App\Domains\Listing\Listeners;

use App\Domains\Identity\Events\UserBlocked;
use App\Domains\Listing\Actions\DeactivateListingsOfUser;

/**
 * Deactivate the blocked user's active listings (ADM-4, LST-T15).
 *
 * Runs after the block: active-item cancellation (which returns listings
 * to active) already happened inside the block transaction, before the
 * UserBlocked event was dispatched. Activating the user does not
 * reactivate listings; the seller does that via the existing action.
 */
final class DeactivateListingsOnUserBlocked
{
    public function __construct(private readonly DeactivateListingsOfUser $deactivate) {}

    public function handle(UserBlocked $event): void
    {
        $this->deactivate->handle($event->userId);
    }
}
