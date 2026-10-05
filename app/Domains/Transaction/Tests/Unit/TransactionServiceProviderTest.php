<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Unit;

use App\Domains\Transaction\Providers\TransactionServiceProvider;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class TransactionServiceProviderTest extends TestCase
{
    public function test_service_provider_is_registered_and_boots(): void
    {
        $provider = $this->app->getProvider(TransactionServiceProvider::class);

        $this->assertInstanceOf(TransactionServiceProvider::class, $provider);
        $this->assertTrue(View::exists('transaction::index'));
    }
}
