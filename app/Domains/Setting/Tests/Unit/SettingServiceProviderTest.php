<?php

declare(strict_types=1);

namespace App\Domains\Setting\Tests\Unit;

use App\Domains\Setting\Providers\SettingServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class SettingServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(SettingServiceProvider::class);

        $this->assertInstanceOf(SettingServiceProvider::class, $provider);
        $this->assertTrue(View::exists('setting::index'));
    }
}
