<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('resolves the Ledger contract from the service container', function () {
    expect(app(Ledger::class))->toBeInstanceOf(Ledger::class);
});

it('posts access and inspection payouts through the contract', function () {
    $ledger = app(Ledger::class);
    $inspector = User::factory()->inspector()->create();

    $access = Payment::factory()->success()->create(['purpose' => PaymentPurpose::Access, 'amount' => 15000]);
    $inspection = Payment::factory()->success()->create(['purpose' => PaymentPurpose::Inspection, 'amount' => 50000]);

    $ledger->postAccessPayout($access->id, $inspector->id);
    $ledger->postInspectionPayout($inspection->id, $inspector->id);

    expect(LedgerEntry::query()->where('payment_id', $access->id)->get()->sum('amount'))->toBe(15000)
        ->and(LedgerEntry::query()->where('payment_id', $inspection->id)->get()->sum('amount'))->toBe(50000)
        ->and($ledger->balanceFor($inspector->id))->toBe(13500 + 45000);
});

it('PAY-T12 computes inspector balance as the sum of own credit entries', function () {
    $ledger = app(Ledger::class);
    $first = User::factory()->inspector()->create();
    $second = User::factory()->inspector()->create();

    $firstAccess = Payment::factory()->success()->create(['purpose' => PaymentPurpose::Access, 'amount' => 15000]);
    $firstInspection = Payment::factory()->success()->create(['purpose' => PaymentPurpose::Inspection, 'amount' => 50000]);
    $secondAccess = Payment::factory()->success()->create(['purpose' => PaymentPurpose::Access, 'amount' => 15000]);

    $ledger->postAccessPayout($firstAccess->id, $first->id);
    $ledger->postInspectionPayout($firstInspection->id, $first->id);
    $ledger->postAccessPayout($secondAccess->id, $second->id);

    expect($ledger->balanceFor($first->id))->toBe(13500 + 45000)
        ->and($ledger->balanceFor($second->id))->toBe(13500);
});

it('returns zero balance for an inspector without entries', function () {
    $inspector = User::factory()->inspector()->create();

    expect(app(Ledger::class)->balanceFor($inspector->id))->toBe(0);
});

it('excludes refunded payments from inspector balance', function () {
    $ledger = app(Ledger::class);
    $payer = User::factory()->create();
    $inspector = User::factory()->inspector()->create();

    $earning = Payment::factory()->success()->create(['purpose' => PaymentPurpose::Access, 'amount' => 15000]);
    $refunded = Payment::factory()->forPayer($payer)->success()->create([
        'purpose' => PaymentPurpose::Inspection,
        'amount' => 50000,
    ]);

    $ledger->postAccessPayout($earning->id, $inspector->id);
    $ledger->refundPayment($refunded->id);

    expect($refunded->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($ledger->balanceFor($inspector->id))->toBe(13500);
});
