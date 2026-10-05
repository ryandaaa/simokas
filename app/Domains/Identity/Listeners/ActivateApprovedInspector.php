<?php

declare(strict_types=1);

namespace App\Domains\Identity\Listeners;

use App\Domains\Identity\Actions\ActivateUser;
use App\Domains\Inspector\Events\InspectorApproved;

final class ActivateApprovedInspector
{
    public function __construct(private readonly ActivateUser $activateUser) {}

    public function handle(InspectorApproved $event): void
    {
        $this->activateUser->handle($event->userId);
    }
}
