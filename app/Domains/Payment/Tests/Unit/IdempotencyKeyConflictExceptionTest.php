<?php

declare(strict_types=1);

use App\Domains\Payment\Exceptions\IdempotencyKeyConflictException;
use App\Support\Exceptions\DomainException;
use Tests\TestCase;

uses(TestCase::class);

it('exposes machine code, conflict status, and Indonesian message without leaking bill data', function () {
    $exception = new IdempotencyKeyConflictException;

    expect($exception)->toBeInstanceOf(DomainException::class)
        ->and($exception->getErrorCode())->toBe('IDEMPOTENCY_KEY_CONFLICT')
        ->and($exception->getStatusCode())->toBe(409)
        ->and($exception->getMessage())->toBe(__('payment.errors.idempotency_key_conflict'))
        ->and($exception->getContext())->toBe([]);
});
