<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Http\Middleware;

use App\Domains\Inspector\Contracts\InspectorEligibility;
use App\Support\Exceptions\DomainException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureInspectorIsApproved
{
    public function __construct(private readonly InspectorEligibility $eligibility) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            if ($request->is('api/*') || $request->expectsJson()) {
                throw new DomainException(
                    __('identity.errors.unauthenticated'),
                    DomainException::CODE_UNAUTHENTICATED,
                    401,
                );
            }

            return redirect()->guest(route('login'));
        }

        if (! $this->eligibility->canViewAndTakeJobs($user)) {
            if ($request->is('api/*') || $request->expectsJson()) {
                throw new DomainException(
                    __('inspector.errors.not_approved'),
                    DomainException::CODE_NOT_ALLOWED,
                    403,
                );
            }

            abort(403, __('inspector.errors.not_approved'));
        }

        return $next($request);
    }
}
