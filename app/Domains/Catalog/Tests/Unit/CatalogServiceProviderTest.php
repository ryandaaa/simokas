<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Tests\Unit;

use App\Domains\Catalog\Providers\CatalogServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class CatalogServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(CatalogServiceProvider::class);

        $this->assertInstanceOf(CatalogServiceProvider::class, $provider);
        $this->assertTrue(View::exists('catalog::index'));
    }
}
