<?php

declare(strict_types=1);

namespace App\Domains\Chat\Tests\Unit;

use App\Domains\Chat\Providers\ChatServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class ChatServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(ChatServiceProvider::class);

        $this->assertInstanceOf(ChatServiceProvider::class, $provider);
        $this->assertTrue(View::exists('chat::index'));
    }
}
