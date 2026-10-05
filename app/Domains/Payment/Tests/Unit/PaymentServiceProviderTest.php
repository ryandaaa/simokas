<?php

declare(strict_types=1);

namespace App\Domains\Payment\Tests\Unit;

use App\Domains\Payment\Providers\PaymentServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class PaymentServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(PaymentServiceProvider::class);

        $this->assertInstanceOf(PaymentServiceProvider::class, $provider);
        $this->assertTrue(View::exists('payment::index'));
    }
}
