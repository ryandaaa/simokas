<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Tests\Unit;

use App\Domains\Inspector\Contracts\InspectorEligibility;
use App\Domains\Inspector\Contracts\InspectorVerificationContract;
use App\Domains\Inspector\Providers\InspectorServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class InspectorServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(InspectorServiceProvider::class);

        $this->assertInstanceOf(InspectorServiceProvider::class, $provider);
        $this->assertTrue(View::exists('inspector::index'));
        $this->assertTrue($this->app->bound(InspectorEligibility::class));
        $this->assertTrue($this->app->bound(InspectorVerificationContract::class));
        $this->assertArrayHasKey('verified_inspector', $this->app['router']->getMiddleware());
    }
}
