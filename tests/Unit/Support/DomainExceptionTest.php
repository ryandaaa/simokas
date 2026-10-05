<?php

declare(strict_types=1);

use App\Support\DomainException as RootDomainException;
use App\Support\Exceptions\DomainException;

it('instantiates with default message, machine code and 422 status', function (): void {
    $e = new DomainException;

    expect($e->getMessage())->toBe('Terjadi pelanggaran aturan bisnis.')
        ->and($e->getErrorCode())->toBe(DomainException::CODE_BUSINESS_RULE)
        ->and($e->getStatusCode())->toBe(422)
        ->and($e->getContext())->toBe([]);
});

it('accepts custom message, machine error code and status code', function (): void {
    $e = new DomainException(
        message: 'Tagihan pembayaran telah kedaluwarsa.',
        errorCode: DomainException::CODE_PAYMENT_EXPIRED,
        statusCode: 410,
        context: ['bill_id' => 'abc-123'],
    );

    expect($e->getMessage())->toBe('Tagihan pembayaran telah kedaluwarsa.')
        ->and($e->getErrorCode())->toBe('PAYMENT_EXPIRED')
        ->and($e->errorCode())->toBe('PAYMENT_EXPIRED')
        ->and($e->getStatusCode())->toBe(410)
        ->and($e->statusCode())->toBe(410)
        ->and($e->getContext())->toBe(['bill_id' => 'abc-123'])
        ->and($e->context())->toBe(['bill_id' => 'abc-123']);
});

it('resolves HTTP status code from known PRD 19.11 error codes automatically when status is omitted', function (string $errorCode, int $expectedStatus): void {
    $e = new DomainException(message: 'Error', errorCode: $errorCode);

    expect($e->getStatusCode())->toBe($expectedStatus);
})->with([
    'VALIDATION_ERROR' => ['VALIDATION_ERROR', 400],
    'UNAUTHENTICATED' => ['UNAUTHENTICATED', 401],
    'NOT_ALLOWED' => ['NOT_ALLOWED', 403],
    'NOT_FOUND' => ['NOT_FOUND', 404],
    'CONFLICT' => ['CONFLICT', 409],
    'PAYMENT_EXPIRED' => ['PAYMENT_EXPIRED', 410],
    'BUSINESS_RULE' => ['BUSINESS_RULE', 422],
    'RATE_LIMITED' => ['RATE_LIMITED', 429],
    'INTERNAL_ERROR' => ['INTERNAL_ERROR', 500],
    // Known domain codes from PRD 19
    'JOB_ALREADY_TAKEN' => ['JOB_ALREADY_TAKEN', 409],
    'MAX_ACTIVE_JOBS' => ['MAX_ACTIVE_JOBS', 409],
    'INSPECTOR_NOT_VERIFIED' => ['INSPECTOR_NOT_VERIFIED', 403],
    'REQUIRED_ITEMS_MISSING' => ['REQUIRED_ITEMS_MISSING', 422],
]);

it('falls back to 422 for custom domain error code when status is not mapped', function (): void {
    $e = new DomainException(message: 'Aturan khusus dilanggar.', errorCode: 'CUSTOM_UNKNOWN_RULE');

    expect($e->getStatusCode())->toBe(422)
        ->and($e->getErrorCode())->toBe('CUSTOM_UNKNOWN_RULE');
});

it('supports domain exception subclassing with pre-configured codes', function (): void {
    $subclass = new class extends DomainException
    {
        public function __construct()
        {
            parent::__construct(
                message: 'Job sudah diambil oleh inspektor lain.',
                errorCode: 'JOB_ALREADY_TAKEN',
                statusCode: 409,
            );
        }
    };

    expect($subclass)->toBeInstanceOf(DomainException::class)
        ->and($subclass->getMessage())->toBe('Job sudah diambil oleh inspektor lain.')
        ->and($subclass->getErrorCode())->toBe('JOB_ALREADY_TAKEN')
        ->and($subclass->getStatusCode())->toBe(409);
});

it('allows importing from App\Support\DomainException alias', function (): void {
    $e = new RootDomainException('Test', 'TEST_CODE', 422);

    expect($e)->toBeInstanceOf(DomainException::class)
        ->and($e)->toBeInstanceOf(RootDomainException::class)
        ->and($e->getErrorCode())->toBe('TEST_CODE');
});
