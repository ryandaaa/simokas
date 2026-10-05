<?php

declare(strict_types=1);

namespace App\Domains\Identity\Http\Middleware;

use App\Support\Exceptions\DomainException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
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

        if ($user->isBlocked()) {
            Auth::logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                throw new DomainException(
                    __('identity.login.blocked_message'),
                    DomainException::CODE_NOT_ALLOWED,
                    403,
                );
            }

            return redirect()->route('identity.blocked');
        }

        $allowedRoles = [];
        foreach ($roles as $role) {
            foreach (explode(',', $role) as $r) {
                $trimmed = trim($r);
                if ($trimmed !== '') {
                    $allowedRoles[] = $trimmed;
                }
            }
        }

        $userRoleValue = $user->role->value;

        if (! in_array($userRoleValue, $allowedRoles, true)) {
            if ($request->is('api/*') || $request->expectsJson()) {
                throw new DomainException(
                    __('identity.errors.forbidden'),
                    DomainException::CODE_NOT_ALLOWED,
                    403,
                );
            }

            abort(403);
        }

        return $next($request);
    }
}
