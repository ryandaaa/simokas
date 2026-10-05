<?php

declare(strict_types=1);

use App\Domains\Payment\Enums\PaymentMethod;
use Tests\TestCase;

uses(TestCase::class);

it('provides expected labels for payment methods', function (PaymentMethod $method, string $expectedLabel) {
    expect($method->label())->toBe($expectedLabel);
})->with([
    [PaymentMethod::Qris, 'QRIS'],
    [PaymentMethod::Transfer, 'Transfer Bank'],
    [PaymentMethod::Ewallet, 'E-Wallet'],
]);
