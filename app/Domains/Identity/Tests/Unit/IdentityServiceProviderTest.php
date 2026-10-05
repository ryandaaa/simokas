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
    }
}
