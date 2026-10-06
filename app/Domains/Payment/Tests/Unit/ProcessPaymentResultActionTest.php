<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Actions\ProcessPaymentResult;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentSimulationOutcome;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Payment\Exceptions\InvalidPaymentStatusTransitionException;
use App\Domains\Payment\Exceptions\PaymentExpiredException;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('PAY-T04 processes simulation outcome success, updates status, and dispatches PaymentSucceeded event', function () {
    Event::fake([PaymentSucceeded::class]);

    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create();

    $action = app(ProcessPaymentResult::class);

    $result = $action->handle(
        $payment,
        PaymentMethod::Qris,
        PaymentSimulationOutcome::Success,
    );

    expect($result->status)->toBe(PaymentStatus::Success)
        ->and($result->method)->toBe(PaymentMethod::Qris)
        ->and($result->paid_at)->not->toBeNull();

    Event::assertDispatched(PaymentSucceeded::class, function (PaymentSucceeded $event) use ($payment, $payer) {
        return $event->paymentId === $payment->id
            && $event->purpose === $payment->purpose
            && $event->refId === $payment->ref_id
            && $event->amount === $payment->amount
            && $event->payerId === $payer->id;
    });
});

it('PAY-T04 processes simulation outcome failed and does not dispatch PaymentSucceeded event', function () {
    Event::fake([PaymentSucceeded::class]);

    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create();

    $action = app(ProcessPaymentResult::class);

    $result = $action->handle(
        $payment,
        PaymentMethod::Transfer,
        PaymentSimulationOutcome::Failed,
    );

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->method)->toBe(PaymentMethod::Transfer)
        ->and($result->paid_at)->toBeNull();

    Event::assertNotDispatched(PaymentSucceeded::class);
});

it('PAY-T04 processes simulation outcome pending and keeps pending status without dispatching event', function () {
    Event::fake([PaymentSucceeded::class]);

    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create();

    $action = app(ProcessPaymentResult::class);

    $result = $action->handle(
        $payment,
        PaymentMethod::Ewallet,
        PaymentSimulationOutcome::Pending,
    );

    expect($result->status)->toBe(PaymentStatus::Pending)
        ->and($result->method)->toBe(PaymentMethod::Ewallet)
        ->and($result->paid_at)->toBeNull();

    Event::assertNotDispatched(PaymentSucceeded::class);
});

it('throws PaymentExpiredException when trying to process an expired payment', function () {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->expired()->create();

    $action = app(ProcessPaymentResult::class);

    $action->handle(
        $payment,
        PaymentMethod::Qris,
        PaymentSimulationOutcome::Success,
    );
})->throws(PaymentExpiredException::class);

it('conditional update prevents double processing when status has already changed', function () {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create();

    // First processing succeeds
    $action = app(ProcessPaymentResult::class);

    $action->handle(
        $payment,
        PaymentMethod::Qris,
        PaymentSimulationOutcome::Success,
    );

    // Second processing on already success payment throws exception
    $action = app(ProcessPaymentResult::class);

    $action->handle(
        $payment,
        PaymentMethod::Qris,
        PaymentSimulationOutcome::Success,
    );
})->throws(InvalidPaymentStatusTransitionException::class);

it('PAY-T05 allows retrying a failed payment with a new idempotency key', function () {
    Event::fake([PaymentSucceeded::class]);

    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->failed()->create();
    $oldKey = $payment->idempotency_key;
    $newKey = 'new-retry-key-999';

    $action = app(ProcessPaymentResult::class);

    $result = $action->handle(
        $payment,
        PaymentMethod::Qris,
        PaymentSimulationOutcome::Success,
        $newKey,
    );

    expect($result->status)->toBe(PaymentStatus::Success)
        ->and($result->idempotency_key)->toBe($newKey)
        ->and($result->idempotency_key)->not->toBe($oldKey);

    Event::assertDispatched(PaymentSucceeded::class);
});

it('PAY-T14 rolls back database transaction completely when mid-flight error occurs', function () {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->pending()->create();

    // Listen to PaymentSucceeded and throw exception during event handling
    Event::listen(PaymentSucceeded::class, function () {
        throw new RuntimeException('Mid-flight listener failure simulation');
    });

    try {
        $action = app(ProcessPaymentResult::class);

        $action->handle(
            $payment,
            PaymentMethod::Qris,
            PaymentSimulationOutcome::Success,
        );
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Mid-flight listener failure simulation');
    }

    // Verify row was rolled back to initial state
    $fresh = $payment->fresh();
    expect($fresh->status)->toBe(PaymentStatus::Pending)
        ->and($fresh->paid_at)->toBeNull()
        ->and($fresh->method)->toBeNull();
});
