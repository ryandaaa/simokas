<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Unit;

use App\Domains\Result\Providers\ResultServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class ResultServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(ResultServiceProvider::class);

        $this->assertInstanceOf(ResultServiceProvider::class, $provider);
        $this->assertTrue(View::exists('result::index'));
    }
}
