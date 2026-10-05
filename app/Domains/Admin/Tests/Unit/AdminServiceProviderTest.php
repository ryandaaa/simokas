<?php

declare(strict_types=1);

namespace App\Domains\Admin\Tests\Unit;

use App\Domains\Admin\Providers\AdminServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class AdminServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(AdminServiceProvider::class);

        $this->assertInstanceOf(AdminServiceProvider::class, $provider);
        $this->assertTrue(View::exists('admin::index'));
    }
}
