<?php

declare(strict_types=1);

namespace App\Domains\Result\Exceptions;

use App\Support\Exceptions\DomainException;

final class MinPhotosNotMetException extends DomainException
{
    /**
     * @param  array<int, array{id: string, label: string}>  $missingPhotoItems
     */
    public static function withItems(array $missingPhotoItems): self
    {
        return new self(
            'Foto minimal belum terpenuhi untuk beberapa item wajib foto.',
            'MIN_PHOTOS_NOT_MET',
            422,
            null,
            ['items' => $missingPhotoItems],
        );
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    public function getMissingPhotoItems(): array
    {
        /** @var array<int, array{id: string, label: string}> $items */
        $items = $this->context['items'] ?? [];

        return $items;
    }
}
