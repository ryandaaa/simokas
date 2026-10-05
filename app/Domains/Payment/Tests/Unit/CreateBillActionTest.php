<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Actions\CreateBill;
use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a bill with server calculated amount and defaults to 30 minutes expiry', function () {
    $action = new CreateBill;
    $payer = User::factory()->create();
    $refId = (string) Str::uuid();
    $key = (string) Str::uuid();

    $bill = $action->handle(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: $refId,
        amount: 50000,
        idempotencyKey: $key,
    ));

    expect($bill)->toBeInstanceOf(Payment::class)
        ->and($bill->payer_id)->toBe($payer->id)
        ->and($bill->purpose)->toBe(PaymentPurpose::Inspection)
        ->and($bill->ref_id)->toBe($refId)
        ->and($bill->amount)->toBe(50000)
        ->and($bill->status)->toBe(PaymentStatus::Pending)
        ->and($bill->idempotency_key)->toBe($key)
        ->and($bill->expires_at)->not->toBeNull()
        ->and($bill->isExpired())->toBeFalse();
});

it('PAY-T03 returns identical payment on duplicate idempotency key without creating duplicate rows', function () {
    $action = new CreateBill;
    $payer = User::factory()->create();
    $refId = (string) Str::uuid();
    $key = 'idemp-key-fixed-123';

    $first = $action->handle(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: $refId,
        amount: 50000,
        idempotencyKey: $key,
    ));

    $second = $action->handle(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: $refId,
        amount: 50000,
        idempotencyKey: $key,
    ));

    expect($second->id)->toBe($first->id)
        ->and(Payment::query()->where('idempotency_key', $key)->count())->toBe(1);
});

it('uses server amount and ignores any client amount variation on identical idempotency key', function () {
    $action = new CreateBill;
    $payer = User::factory()->create();
    $refId = (string) Str::uuid();
    $key = 'idemp-key-fixed-amount';

    $first = $action->handle(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: $refId,
        amount: 50000,
        idempotencyKey: $key,
    ));

    // Subsequent request attempting to pass modified/spoofed amount
    $second = $action->handle(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: $refId,
        amount: 1000, // Spoofed amount ignored
        idempotencyKey: $key,
    ));

    expect($second->id)->toBe($first->id)
        ->and($second->amount)->toBe(50000);
});
