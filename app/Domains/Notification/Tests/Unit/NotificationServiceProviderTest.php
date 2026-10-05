<?php

declare(strict_types=1);

namespace App\Domains\Notification\Tests\Unit;

use App\Domains\Notification\Providers\NotificationServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class NotificationServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(NotificationServiceProvider::class);

        $this->assertInstanceOf(NotificationServiceProvider::class, $provider);
        $this->assertTrue(View::exists('notification::index'));
    }
}
