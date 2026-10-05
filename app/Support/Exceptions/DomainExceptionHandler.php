<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DomainExceptionHandler
{
    public static function render(DomainException $e, Request $request): ?JsonResponse
    {
        if (! ($request->is('api/*') || $request->expectsJson())) {
            return null;
        }

        $errorPayload = [
            'code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
        ];

        $context = $e->getContext();
        if (! empty($context)) {
            if (isset($context['errors']) && is_array($context['errors'])) {
                $errorPayload['errors'] = $context['errors'];
            } else {
                $errorPayload['errors'] = $context;
            }
        }

        return response()->json([
            'data' => null,
            'error' => $errorPayload,
        ], $e->getStatusCode());
    }
}
