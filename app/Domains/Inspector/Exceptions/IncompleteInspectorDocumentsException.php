<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Exceptions;

use App\Support\Exceptions\DomainException;

final class IncompleteInspectorDocumentsException extends DomainException
{
    /**
     * @param  array<int, string>  $missingTypes
     */
    public static function withMissing(array $missingTypes): self
    {
        return new self(
            __('inspector.errors.incomplete_documents', [
                'missing' => implode(', ', $missingTypes),
            ]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['missing_types' => $missingTypes],
        );
    }
}
