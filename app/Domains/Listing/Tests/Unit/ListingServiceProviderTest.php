<?php

declare(strict_types=1);

namespace App\Domains\Listing\Tests\Unit;

use App\Domains\Listing\Providers\ListingServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class ListingServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(ListingServiceProvider::class);

        $this->assertInstanceOf(ListingServiceProvider::class, $provider);
        $this->assertTrue(View::exists('listing::index'));
    }
}
