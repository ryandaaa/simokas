<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Contracts\Billing;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Setting\Enums\SettingKey;
use App\Domains\Setting\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function setPlatformFeePct(int $pct): void
{
    AppSetting::updateOrCreate(['key' => SettingKey::PlatformFeePct->value], ['value' => $pct]);
}

it('PAY-T08 posts access ledger entries when an access payment succeeds', function () {
    $inspector = User::factory()->inspector()->create();
    $payment = Payment::factory()->success()->create([
        'purpose' => PaymentPurpose::Access,
        'amount' => 50000,
    ]);

    event(new PaymentSucceeded(
        paymentId: $payment->id,
        purpose: PaymentPurpose::Access,
        refId: $payment->ref_id,
        amount: (int) $payment->amount,
        payerId: $payment->payer_id,
        beneficiaryId: $inspector->id,
    ));

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();

    expect($entries)->toHaveCount(2)
        ->and($entries->sum('amount'))->toBe(50000)
        ->and((int) $entries->firstWhere('entry_type', LedgerEntryType::PlatformFee)->amount)->toBe(5000)
        ->and($entries->firstWhere('entry_type', LedgerEntryType::AccessFee)->account_user_id)->toBe($inspector->id);
});

it('ignores success events for non-access purposes without posting', function (PaymentPurpose $purpose) {
    $inspector = User::factory()->inspector()->create();
    $payment = Payment::factory()->success()->create([
        'purpose' => $purpose,
        'amount' => 50000,
    ]);

    event(new PaymentSucceeded(
        paymentId: $payment->id,
        purpose: $purpose,
        refId: $payment->ref_id,
        amount: (int) $payment->amount,
        payerId: $payment->payer_id,
        beneficiaryId: $inspector->id,
    ));

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
})->with([
    'inspection waits for job completion' => [PaymentPurpose::Inspection],
    'purchase waits for transaction completion' => [PaymentPurpose::Purchase],
]);

it('skips access posting when the beneficiary inspector is unknown', function () {
    $payment = Payment::factory()->success()->create([
        'purpose' => PaymentPurpose::Access,
        'amount' => 50000,
    ]);

    event(new PaymentSucceeded(
        paymentId: $payment->id,
        purpose: PaymentPurpose::Access,
        refId: $payment->ref_id,
        amount: (int) $payment->amount,
        payerId: $payment->payer_id,
    ));

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
});

it('PAY-T13 keeps total ledger entries equal to the payment value across purposes, amounts and percentages', function (PaymentPurpose $purpose, LedgerEntryType $creditType, int $amount, int $pct) {
    setPlatformFeePct($pct);

    $inspector = User::factory()->inspector()->create();
    $payment = Payment::factory()->success()->create([
        'purpose' => $purpose,
        'amount' => $amount,
    ]);

    $ledger = app(Ledger::class);

    match ($purpose) {
        PaymentPurpose::Access => $ledger->postAccessPayout($payment->id, $inspector->id),
        PaymentPurpose::Inspection => $ledger->postInspectionPayout($payment->id, $inspector->id),
        default => null,
    };

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();

    expect($entries)->toHaveCount(2)
        ->and($entries->sum('amount'))->toBe($amount)
        ->and(
            (int) $entries->firstWhere('entry_type', LedgerEntryType::PlatformFee)->amount
            + (int) $entries->firstWhere('entry_type', $creditType)->amount
        )->toBe($amount);
})->with('payout invariant matrix');

dataset('payout invariant matrix', function () {
    $matrix = [];

    foreach ([PaymentPurpose::Access, PaymentPurpose::Inspection] as $purpose) {
        $creditType = $purpose === PaymentPurpose::Access ? LedgerEntryType::AccessFee : LedgerEntryType::InspectionFee;

        foreach ([1, 2, 3, 99, 100, 101, 15000, 15005, 50000, 999999, 100000000] as $amount) {
            foreach ([0, 10, 50, 100] as $pct) {
                $matrix["{$purpose->value} {$amount} at {$pct}%"] = [$purpose, $creditType, $amount, $pct];
            }
        }
    }

    return $matrix;
});

it('keeps the invariant for refunded payments with a single refund entry', function () {
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->success()->create([
        'purpose' => PaymentPurpose::Inspection,
        'amount' => 15005,
    ]);

    app(Ledger::class)->refundPayment($payment->id);

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();

    expect($entries)->toHaveCount(1)
        ->and($entries->sum('amount'))->toBe(15005)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
});

it('PAY-T15 does not create a second access bill when access already exists', function () {
    $payer = User::factory()->create();
    $billing = app(Billing::class);
    $refId = (string) Str::uuid();

    $first = $billing->createBill(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Access,
        refId: $refId,
        amount: 15000,
        idempotencyKey: (string) Str::uuid(),
    ));

    $second = $billing->createBill(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Access,
        refId: $refId,
        amount: 15000,
        idempotencyKey: (string) Str::uuid(),
    ));

    expect($second->id)->toBe($first->id)
        ->and(Payment::query()->where('payer_id', $payer->id)->where('ref_id', $refId)->count())->toBe(1);
});

it('PAY-T15 reuses a success access bill instead of charging twice', function () {
    $payer = User::factory()->create();
    $billing = app(Billing::class);
    $refId = (string) Str::uuid();

    $paid = Payment::factory()->forPayer($payer)->success()->create([
        'purpose' => PaymentPurpose::Access,
        'ref_id' => $refId,
        'amount' => 15000,
    ]);

    $bill = $billing->createBill(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Access,
        refId: $refId,
        amount: 15000,
        idempotencyKey: (string) Str::uuid(),
    ));

    expect($bill->id)->toBe($paid->id)
        ->and($bill->status)->toBe(PaymentStatus::Success)
        ->and(Payment::query()->where('payer_id', $payer->id)->where('ref_id', $refId)->count())->toBe(1);
});

it('creates a new bill when the previous one can no longer be paid', function (string $state) {
    $payer = User::factory()->create();
    $billing = app(Billing::class);
    $refId = (string) Str::uuid();

    $old = Payment::factory()->forPayer($payer)->expired()->create([
        'purpose' => PaymentPurpose::Access,
        'ref_id' => $refId,
        'status' => $state,
        'amount' => 15000,
    ]);

    $bill = $billing->createBill(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Access,
        refId: $refId,
        amount: 15000,
        idempotencyKey: (string) Str::uuid(),
    ));

    expect($bill->id)->not->toBe($old->id)
        ->and(Payment::query()->where('payer_id', $payer->id)->where('ref_id', $refId)->count())->toBe(2);
})->with([
    'expired pending' => [PaymentStatus::Pending->value],
    'expired failed' => [PaymentStatus::Failed->value],
]);

it('still creates a new bill for a different payer opening the same result', function () {
    $billing = app(Billing::class);
    $refId = (string) Str::uuid();

    $firstPayer = User::factory()->create();
    $secondPayer = User::factory()->create();

    $first = $billing->createBill(new CreateBillData(
        payerId: $firstPayer->id,
        purpose: PaymentPurpose::Access,
        refId: $refId,
        amount: 15000,
        idempotencyKey: (string) Str::uuid(),
    ));

    $second = $billing->createBill(new CreateBillData(
        payerId: $secondPayer->id,
        purpose: PaymentPurpose::Access,
        refId: $refId,
        amount: 15000,
        idempotencyKey: (string) Str::uuid(),
    ));

    expect($second->id)->not->toBe($first->id);
});
