<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Contracts\Billing;
use App\Domains\Payment\DTOs\BillData;
use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('resolves Billing contract implementation from service container', function () {
    $billing = app(Billing::class);

    expect($billing)->toBeInstanceOf(Billing::class);
});

it('creates a bill and returns public BillData DTO instead of internal model', function () {
    $billing = app(Billing::class);
    $payer = User::factory()->create();
    $refId = (string) Str::uuid();
    $key = (string) Str::uuid();

    $dto = $billing->createBill(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: $refId,
        amount: 50000,
        idempotencyKey: $key,
    ));

    expect($dto)->toBeInstanceOf(BillData::class)
        ->and($dto->payerId)->toBe($payer->id)
        ->and($dto->purpose)->toBe(PaymentPurpose::Inspection)
        ->and($dto->refId)->toBe($refId)
        ->and($dto->amount)->toBe(50000)
        ->and($dto->status)->toBe(PaymentStatus::Pending)
        ->and($dto->idempotencyKey)->toBe($key)
        ->and($dto->expiresAt)->not->toBeNull();
});

it('finds an existing bill and returns public BillData DTO or null', function () {
    $billing = app(Billing::class);
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->create();

    $found = $billing->findBill($payment->id);
    expect($found)->toBeInstanceOf(BillData::class)
        ->and($found->id)->toBe($payment->id);

    $missing = $billing->findBill((string) Str::uuid());
    expect($missing)->toBeNull();
});

it('gets an existing bill or throws ModelNotFoundException when not found', function () {
    $billing = app(Billing::class);
    $payer = User::factory()->create();
    $payment = Payment::factory()->forPayer($payer)->create();

    $bill = $billing->getBill($payment->id);
    expect($bill)->toBeInstanceOf(BillData::class)
        ->and($bill->id)->toBe($payment->id);

    $billing->getBill((string) Str::uuid());
})->throws(ModelNotFoundException::class);
