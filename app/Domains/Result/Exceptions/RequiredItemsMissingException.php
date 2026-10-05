<?php

declare(strict_types=1);

namespace App\Domains\Result\Exceptions;

use App\Support\Exceptions\DomainException;

final class RequiredItemsMissingException extends DomainException
{
    /**
     * @param  array<int, array{id: string, label: string}>  $missingItems
     */
    public static function withItems(array $missingItems): self
    {
        return new self(
            'Semua item wajib harus dinilai sebelum hasil dapat dikirim.',
            'REQUIRED_ITEMS_MISSING',
            422,
            null,
            ['items' => $missingItems],
        );
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    public function getMissingItems(): array
    {
        /** @var array<int, array{id: string, label: string}> $items */
        $items = $this->context['items'] ?? [];

        return $items;
    }
}
