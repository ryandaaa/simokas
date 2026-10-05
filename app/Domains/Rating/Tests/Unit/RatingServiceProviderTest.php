<?php

declare(strict_types=1);

namespace App\Domains\Rating\Tests\Unit;

use App\Domains\Rating\Providers\RatingServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class RatingServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(RatingServiceProvider::class);

        $this->assertInstanceOf(RatingServiceProvider::class, $provider);
        $this->assertTrue(View::exists('rating::index'));
    }
}
