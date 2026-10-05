<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Http\Middleware\EnsureUserHasRole;
use App\Domains\Identity\Models\User;
use App\Support\Exceptions\DomainException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class EnsureUserHasRoleTest extends TestCase
{
    private EnsureUserHasRole $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureUserHasRole;
    }

    public function test_allows_request_when_user_has_required_role(): void
    {
        $user = new User([
            'id' => '00000000-0000-0000-0000-000000000001',
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $request = Request::create('/admin-zone', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'admin');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    public function test_allows_request_when_user_matches_one_of_multiple_comma_separated_roles(): void
    {
        $user = new User([
            'id' => '00000000-0000-0000-0000-000000000002',
            'role' => UserRole::Inspector,
            'status' => UserStatus::Active,
        ]);

        $request = Request::create('/shared-zone', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'user,inspector');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_aborts_with_403_for_web_request_when_role_does_not_match(): void
    {
        $this->expectException(HttpException::class);

        $user = new User([
            'id' => '00000000-0000-0000-0000-000000000003',
            'role' => UserRole::User,
            'status' => UserStatus::Active,
        ]);

        $request = Request::create('/admin-only', 'GET');
        $request->setUserResolver(fn () => $user);

        try {
            $this->middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            }, 'admin');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_throws_domain_exception_not_allowed_for_json_request_when_role_does_not_match(): void
    {
        $user = new User([
            'id' => '00000000-0000-0000-0000-000000000004',
            'role' => UserRole::User,
            'status' => UserStatus::Active,
        ]);

        $request = Request::create('/admin-only', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(fn () => $user);

        $this->expectException(DomainException::class);

        try {
            $this->middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            }, 'admin');
        } catch (DomainException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame(DomainException::CODE_NOT_ALLOWED, $e->getErrorCode());
            throw $e;
        }
    }

    public function test_rejects_unauthenticated_web_request_with_redirect_to_login(): void
    {
        $request = Request::create('/protected', 'GET');
        $request->setUserResolver(fn () => null);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'user');

        $this->assertTrue($response->isRedirection());
        $this->assertSame(route('login'), $response->headers->get('Location'));
    }

    public function test_rejects_unauthenticated_json_request_with_401_domain_exception(): void
    {
        $request = Request::create('/protected', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(fn () => null);

        $this->expectException(DomainException::class);

        try {
            $this->middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            }, 'user');
        } catch (DomainException $e) {
            $this->assertSame(401, $e->getStatusCode());
            $this->assertSame(DomainException::CODE_UNAUTHENTICATED, $e->getErrorCode());
            throw $e;
        }
    }

    public function test_rejects_blocked_web_user_and_redirects_to_blocked(): void
    {
        $user = new User([
            'id' => '00000000-0000-0000-0000-000000000005',
            'role' => UserRole::User,
            'status' => UserStatus::Blocked,
        ]);

        $request = Request::create('/protected', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'user');

        $this->assertTrue($response->isRedirection());
        $this->assertSame(route('identity.blocked'), $response->headers->get('Location'));
    }

    public function test_rejects_blocked_json_user_with_403_domain_exception(): void
    {
        $user = new User([
            'id' => '00000000-0000-0000-0000-000000000006',
            'role' => UserRole::User,
            'status' => UserStatus::Blocked,
        ]);

        $request = Request::create('/protected', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(fn () => $user);

        $this->expectException(DomainException::class);

        try {
            $this->middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            }, 'user');
        } catch (DomainException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame(DomainException::CODE_NOT_ALLOWED, $e->getErrorCode());
            throw $e;
        }
    }

    public function test_rejects_when_role_is_unknown(): void
    {
        $this->expectException(HttpException::class);

        $user = new User([
            'id' => '00000000-0000-0000-0000-000000000007',
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $request = Request::create('/protected', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        }, 'non_existent_role');
    }
}
