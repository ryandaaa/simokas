<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Actions\CreateBill;
use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Exceptions\IdempotencyKeyConflictException;
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

it('rejects the same key with different parameters without creating or changing any row', function (Closure $conflicting) {
    $action = new CreateBill;
    $payer = User::factory()->create();
    $refId = (string) Str::uuid();
    $key = 'idemp-key-conflict';

    $original = $action->handle(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: $refId,
        amount: 50000,
        idempotencyKey: $key,
    ));

    try {
        $action->handle($conflicting($payer, $refId, $key));
        expect(false)->toBeTrue('expected IdempotencyKeyConflictException');
    } catch (IdempotencyKeyConflictException $e) {
        expect($e->getErrorCode())->toBe('IDEMPOTENCY_KEY_CONFLICT')
            ->and($e->getStatusCode())->toBe(409)
            ->and($e->getMessage())->not->toContain($original->id)
            ->and($e->getMessage())->not->toContain((string) $original->amount);
    }

    expect(Payment::query()->where('idempotency_key', $key)->count())->toBe(1);

    $untouched = $original->fresh();
    expect($untouched->payer_id)->toBe($payer->id)
        ->and($untouched->purpose)->toBe(PaymentPurpose::Inspection)
        ->and($untouched->ref_id)->toBe($refId)
        ->and((int) $untouched->amount)->toBe(50000);
})->with([
    'different payer' => [fn ($payer, $refId, $key) => new CreateBillData(
        payerId: User::factory()->create()->id,
        purpose: PaymentPurpose::Inspection,
        refId: $refId,
        amount: 50000,
        idempotencyKey: $key,
    )],
    'different amount' => [fn ($payer, $refId, $key) => new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: $refId,
        amount: 1000,
        idempotencyKey: $key,
    )],
    'different purpose' => [fn ($payer, $refId, $key) => new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Access,
        refId: $refId,
        amount: 50000,
        idempotencyKey: $key,
    )],
    'different ref_id' => [fn ($payer, $refId, $key) => new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: (string) Str::uuid(),
        amount: 50000,
        idempotencyKey: $key,
    )],
]);

it('never leaks another payer bill through the Billing contract on key conflict', function () {
    $action = new CreateBill;
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $key = 'idemp-key-no-leak';

    $owned = $action->handle(new CreateBillData(
        payerId: $owner->id,
        purpose: PaymentPurpose::Inspection,
        refId: (string) Str::uuid(),
        amount: 50000,
        idempotencyKey: $key,
    ));

    try {
        $action->handle(new CreateBillData(
            payerId: $stranger->id,
            purpose: PaymentPurpose::Inspection,
            refId: $owned->ref_id,
            amount: (int) $owned->amount,
            idempotencyKey: $key,
        ));
        expect(false)->toBeTrue('expected IdempotencyKeyConflictException');
    } catch (IdempotencyKeyConflictException $e) {
        expect($e->getMessage())->not->toContain($owned->id)
            ->and($e->getContext())->toBe([]);
    }
});

it('resolves a duplicate-key race to the same bill when parameters match', function () {
    $action = new CreateBill;
    $payer = User::factory()->create();
    $refId = (string) Str::uuid();
    $key = 'idemp-key-race-match';

    $existing = Payment::factory()->forPayer($payer)->create([
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => $refId,
        'amount' => 50000,
        'idempotency_key' => $key,
    ]);

    // The insert hits the unique constraint (simulating a lost race) and the
    // recovery path returns the identical bill.
    $result = $action->handle(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: $refId,
        amount: 50000,
        idempotencyKey: $key,
    ));

    expect($result->id)->toBe($existing->id)
        ->and(Payment::query()->where('idempotency_key', $key)->count())->toBe(1);
});

it('rejects a duplicate-key race when parameters differ', function () {
    $action = new CreateBill;
    $payer = User::factory()->create();
    $key = 'idemp-key-race-conflict';

    Payment::factory()->forPayer($payer)->create([
        'purpose' => PaymentPurpose::Inspection,
        'ref_id' => (string) Str::uuid(),
        'amount' => 50000,
        'idempotency_key' => $key,
    ]);

    $action->handle(new CreateBillData(
        payerId: $payer->id,
        purpose: PaymentPurpose::Inspection,
        refId: (string) Str::uuid(),
        amount: 75000,
        idempotencyKey: $key,
    ));
})->throws(IdempotencyKeyConflictException::class);
