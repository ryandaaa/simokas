<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Tests\Unit;

use App\Domains\Dashboard\Providers\DashboardServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class DashboardServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(DashboardServiceProvider::class);

        $this->assertInstanceOf(DashboardServiceProvider::class, $provider);
        $this->assertTrue(View::exists('dashboard::index'));
    }
}
