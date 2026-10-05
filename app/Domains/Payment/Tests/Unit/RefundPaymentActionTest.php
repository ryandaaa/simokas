<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Actions\PostPayout;
use App\Domains\Payment\Actions\RefundPayment;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Exceptions\PaymentAlreadyProcessedException;
use App\Domains\Payment\Exceptions\RefundNotAllowedException;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('PAY-T10 refunds a success payment before posting with a single refund entry', function () {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->success()->create([
        'purpose' => PaymentPurpose::Inspection,
        'amount' => 50000,
    ]);

    $result = app(RefundPayment::class)->handle($payment);

    expect($result->status)->toBe(PaymentStatus::Refunded);

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();

    expect($entries)->toHaveCount(1);

    $refund = $entries->first();

    expect($refund->entry_type)->toBe(LedgerEntryType::Refund)
        ->and($refund->account_user_id)->toBe($payer->id)
        ->and((int) $refund->amount)->toBe(50000)
        ->and($entries->sum('amount'))->toBe(50000);
});

it('PAY-T10 second refund call has effect only once', function () {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->success()->create([
        'purpose' => PaymentPurpose::Inspection,
        'amount' => 50000,
    ]);

    app(RefundPayment::class)->handle($payment);
    $second = app(RefundPayment::class)->handle($payment);

    expect($second->status)->toBe(PaymentStatus::Refunded)
        ->and(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);
});

it('rejects refund for payments that are not success', function (string $state) {
    $payment = Payment::factory()->create([
        'status' => $state,
        'purpose' => PaymentPurpose::Inspection,
    ]);

    expect(fn () => app(RefundPayment::class)->handle($payment))
        ->toThrow(RefundNotAllowedException::class);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0)
        ->and($payment->fresh()->status->value)->toBe($state);
})->with([
    'pending' => ['pending'],
    'failed' => ['failed'],
]);

it('rejects refund for a refunded payment missing its refund entry', function () {
    $payment = Payment::factory()->refunded()->create([
        'purpose' => PaymentPurpose::Inspection,
    ]);

    expect(fn () => app(RefundPayment::class)->handle($payment))
        ->toThrow(PaymentAlreadyProcessedException::class);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
});

it('rejects refund when ledger entries were already posted', function () {
    $payer = User::factory()->create();
    $inspector = User::factory()->inspector()->create();
    $payment = Payment::factory()->forPayer($payer)->success()->create([
        'purpose' => PaymentPurpose::Access,
        'amount' => 50000,
    ]);

    app(PostPayout::class)->handle($payment, $inspector->id, LedgerEntryType::AccessFee);

    expect(fn () => app(RefundPayment::class)->handle($payment))
        ->toThrow(RefundNotAllowedException::class);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Success)
        ->and(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(2);
});

it('leaves no partial entries when refund fails midway', function () {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->success()->create([
        'purpose' => PaymentPurpose::Inspection,
        'amount' => 50000,
    ]);

    Event::listen('eloquent.creating: '.LedgerEntry::class, function () {
        throw new RuntimeException('mid-operation failure while writing the refund entry');
    });

    try {
        app(RefundPayment::class)->handle($payment);

        expect(false)->toBeTrue('refund should have failed while writing the entry');
    } catch (RuntimeException) {
        // Expected: the status update must roll back together with the failed insert.
    } finally {
        Event::forget('eloquent.creating: '.LedgerEntry::class);
    }

    expect($payment->fresh()->status)->toBe(PaymentStatus::Success)
        ->and(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
});

it('exposes idempotent refund through the public Ledger contract', function () {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->success()->create([
        'purpose' => PaymentPurpose::Inspection,
        'amount' => 15000,
    ]);

    $ledger = app(Ledger::class);

    $ledger->refundPayment($payment->id);
    $ledger->refundPayment($payment->id);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(1);
});
