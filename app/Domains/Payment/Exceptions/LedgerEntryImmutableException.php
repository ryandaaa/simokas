<?php

declare(strict_types=1);

namespace App\Domains\Payment\Exceptions;

use App\Support\Exceptions\DomainException;

final class LedgerEntryImmutableException extends DomainException
{
    protected string $errorCode = 'LEDGER_ENTRY_IMMUTABLE';

    protected int $statusCode = 422;

    public static function forUpdate(string $entryId): self
    {
        return new self(
            __('payment.errors.ledger_entry_immutable'),
            'LEDGER_ENTRY_IMMUTABLE',
            422,
            null,
            ['entry_id' => $entryId],
        );
    }

    public static function forDelete(string $entryId): self
    {
        return new self(
            __('payment.errors.ledger_entry_immutable'),
            'LEDGER_ENTRY_IMMUTABLE',
            422,
            null,
            ['entry_id' => $entryId],
        );
    }
}
