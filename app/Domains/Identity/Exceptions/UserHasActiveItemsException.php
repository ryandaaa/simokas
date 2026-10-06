<?php

declare(strict_types=1);

namespace App\Domains\Identity\Exceptions;

use App\Support\Exceptions\DomainException;

final class UserHasActiveItemsException extends DomainException
{
    /**
     * @param  list<array{type: string, id: string}>  $items
     */
    public static function create(array $items): self
    {
        return new self(
            __('identity.errors.user_has_active_items'),
            'USER_HAS_ACTIVE_ITEMS',
            409,
            null,
            ['items' => $items],
        );
    }

    /**
     * @return list<array{type: string, id: string}>
     */
    public function items(): array
    {
        $items = $this->getContext()['items'] ?? [];

        return is_array($items) ? $items : [];
    }
}
