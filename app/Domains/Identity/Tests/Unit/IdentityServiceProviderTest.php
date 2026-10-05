<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Providers\IdentityServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class IdentityServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(IdentityServiceProvider::class);

        $this->assertInstanceOf(IdentityServiceProvider::class, $provider);
        $this->assertTrue(View::exists('identity::index'));
        $this->assertTrue(View::exists('identity::livewire.user-dashboard'));
        $this->assertTrue(View::exists('identity::livewire.inspector-dashboard'));
        $this->assertTrue(View::exists('identity::livewire.admin-dashboard'));

        $middleware = $this->app['router']->getMiddleware();
        $this->assertArrayHasKey('role', $middleware);
        $this->assertArrayHasKey('not_blocked', $middleware);
    }
}
