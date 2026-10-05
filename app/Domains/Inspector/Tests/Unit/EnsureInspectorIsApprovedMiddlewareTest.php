<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Http\Middleware\EnsureInspectorIsApproved;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Support\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('ONB-T08 non-approved inspectors receive 403 when accessing protected inspector route (ONB-E4)', function (VerificationStatus $status) {
    $middleware = app(EnsureInspectorIsApproved::class);

    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Pending,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'verification_status' => $status,
    ]);

    $request = Request::create('/test-jobs', 'GET');
    $request->setUserResolver(fn () => $user);

    expect(fn () => $middleware->handle($request, fn () => response('OK')))
        ->toThrow(HttpException::class);
})->with([
    'pending inspector' => VerificationStatus::Pending,
    'rejected inspector' => VerificationStatus::Rejected,
]);

it('non-approved inspector receives JSON NOT_ALLOWED with 403 on json request', function () {
    $middleware = app(EnsureInspectorIsApproved::class);

    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Pending,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'verification_status' => VerificationStatus::Pending,
    ]);

    $request = Request::create('/api/jobs', 'GET');
    $request->headers->set('Accept', 'application/json');
    $request->setUserResolver(fn () => $user);

    try {
        $middleware->handle($request, fn () => response('OK'));
        expect(false)->toBeTrue('Expected DomainException was not thrown');
    } catch (DomainException $e) {
        expect($e->getStatusCode())->toBe(403)
            ->and($e->getErrorCode())->toBe(DomainException::CODE_NOT_ALLOWED);
    }
});

it('approved inspector passes through successfully', function () {
    $middleware = app(EnsureInspectorIsApproved::class);

    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'verification_status' => VerificationStatus::Approved,
    ]);

    $request = Request::create('/jobs', 'GET');
    $request->setUserResolver(fn () => $user);

    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('unauthenticated html request is redirected to login', function () {
    $middleware = app(EnsureInspectorIsApproved::class);

    $request = Request::create('/jobs', 'GET');
    $request->setUserResolver(fn () => null);

    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->isRedirect(route('login')))->toBeTrue();
});

it('unauthenticated json request receives 401 unauthenticated domain exception', function () {
    $middleware = app(EnsureInspectorIsApproved::class);

    $request = Request::create('/api/jobs', 'GET');
    $request->headers->set('Accept', 'application/json');
    $request->setUserResolver(fn () => null);

    try {
        $middleware->handle($request, fn () => response('OK'));
        expect(false)->toBeTrue('Expected DomainException was not thrown');
    } catch (DomainException $e) {
        expect($e->getStatusCode())->toBe(401)
            ->and($e->getErrorCode())->toBe(DomainException::CODE_UNAUTHENTICATED);
    }
});
