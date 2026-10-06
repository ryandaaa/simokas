<?php

declare(strict_types=1);

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

/*
 * Rute yang memakai middleware auth harus ikut memulai sesi (grup middleware web).
 * Tanpa itu, halaman melempar pengguna yang sudah login ke /login. Test yang memakai
 * actingAs() tidak menangkap kesalahan ini karena melewati sesi, jadi kita periksa
 * konfigurasi middleware rutenya langsung.
 */
it('ROUTE-T01 every route that requires auth also starts the session', function (): void {
    $router = app('router');
    $missing = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        $middleware = $router->resolveMiddleware($route->gatherMiddleware(), $route->excludedMiddleware());

        $requiresAuth = collect($middleware)->contains(
            fn ($m): bool => is_string($m) && str_starts_with($m, Authenticate::class),
        );

        if ($requiresAuth && ! in_array(StartSession::class, $middleware, true)) {
            $missing[] = implode('|', $route->methods()).' /'.ltrim($route->uri(), '/');
        }
    }

    expect($missing)->toBe([]);
});

it('ROUTE-T02 payment, notification and earnings pages keep the session cookie flow', function (string $routeName): void {
    $route = Route::getRoutes()->getByName($routeName);

    expect($route)->not->toBeNull();

    $resolved = app('router')->resolveMiddleware($route->gatherMiddleware(), $route->excludedMiddleware());

    expect($resolved)->toContain(StartSession::class);
})->with([
    'payments.show',
    'payments.simulate',
    'inspector.earnings',
    'notifications.index',
]);
