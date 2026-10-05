<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Actions\ExpirePayment;
use App\Domains\Payment\Exceptions\InvalidPaymentStatusTransitionException;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('expires a pending payment by setting expires_at to the past', function () {
    $action = new ExpirePayment;
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create([
        'expires_at' => now()->addMinutes(30),
    ]);

    expect($payment->isExpired())->toBeFalse();

    $expired = $action->handle($payment);

    expect($expired->isExpired())->toBeTrue();
});

it('refuses to expire an already succeeded payment', function () {
    $action = new ExpirePayment;
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->success()->create();

    $action->handle($payment);
})->throws(InvalidPaymentStatusTransitionException::class);

it('refuses to expire an already refunded payment', function () {
    $action = new ExpirePayment;
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->refunded()->create();

    $action->handle($payment);
})->throws(InvalidPaymentStatusTransitionException::class);
