<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Inspection\Providers\InspectionServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class InspectionServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(InspectionServiceProvider::class);

        $this->assertInstanceOf(InspectionServiceProvider::class, $provider);
        $this->assertTrue(View::exists('inspection::index'));
    }
}
