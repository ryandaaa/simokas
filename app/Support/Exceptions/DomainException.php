<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DomainException extends Exception
{
    public const string CODE_VALIDATION_ERROR = 'VALIDATION_ERROR';

    public const string CODE_UNAUTHENTICATED = 'UNAUTHENTICATED';

    public const string CODE_NOT_ALLOWED = 'NOT_ALLOWED';

    public const string CODE_NOT_FOUND = 'NOT_FOUND';

    public const string CODE_CONFLICT = 'CONFLICT';

    public const string CODE_PAYMENT_EXPIRED = 'PAYMENT_EXPIRED';

    public const string CODE_BUSINESS_RULE = 'BUSINESS_RULE';

    public const string CODE_RATE_LIMITED = 'RATE_LIMITED';

    public const string CODE_INTERNAL_ERROR = 'INTERNAL_ERROR';

    /**
     * @var array<string, int>
     */
    public const array CODE_STATUS_MAP = [
        self::CODE_VALIDATION_ERROR => 400,
        self::CODE_UNAUTHENTICATED => 401,
        self::CODE_NOT_ALLOWED => 403,
        self::CODE_NOT_FOUND => 404,
        self::CODE_CONFLICT => 409,
        self::CODE_PAYMENT_EXPIRED => 410,
        self::CODE_BUSINESS_RULE => 422,
        self::CODE_RATE_LIMITED => 429,
        self::CODE_INTERNAL_ERROR => 500,

        // Domain-specific error codes defined in PRD 19
        'JOB_ALREADY_TAKEN' => 409,
        'MAX_ACTIVE_JOBS' => 409,
        'LISTING_HAS_ACTIVE_JOB' => 409,
        'USER_HAS_ACTIVE_ITEMS' => 409,
        'INSPECTOR_NOT_VERIFIED' => 403,
        'OWN_LISTING' => 403,
        'REQUIRED_ITEMS_MISSING' => 422,
        'MIN_PHOTOS_NOT_MET' => 422,
        'NOT_CAMERA_CAPTURE' => 422,
        'LISTING_NOT_ACTIVE' => 422,
        'FILE_TOO_LARGE' => 413,
        'UNSUPPORTED_TYPE' => 415,
    ];

    protected string $errorCode;

    protected int $statusCode;

    /**
     * @var array<string, mixed>
     */
    protected array $context;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message = 'Terjadi pelanggaran aturan bisnis.',
        ?string $errorCode = null,
        ?int $statusCode = null,
        ?Throwable $previous = null,
        array $context = [],
    ) {
        $this->errorCode = $errorCode ?? ($this->errorCode ?? self::CODE_BUSINESS_RULE);
        $this->statusCode = $statusCode ?? ($this->statusCode ?? (self::CODE_STATUS_MAP[$this->errorCode] ?? 422));
        $this->context = $context;

        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    public function render(Request $request): ?JsonResponse
    {
        return DomainExceptionHandler::render($this, $request);
    }
}
