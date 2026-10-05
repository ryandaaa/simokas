<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Models\InspectorDocument;

final class InspectorDocumentPolicy
{
    public function view(User $user, InspectorDocument $document): bool
    {
        return $user->isAdmin() || $user->id === $document->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isInspector() || $user->isAdmin();
    }

    public function delete(User $user, InspectorDocument $document): bool
    {
        return $user->isAdmin() || $user->id === $document->user_id;
    }
}
