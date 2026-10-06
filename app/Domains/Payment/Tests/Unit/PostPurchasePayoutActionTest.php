<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Actions\PostPurchasePayout;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Exceptions\InvalidLedgerPostingException;
use App\Domains\Payment\Exceptions\LedgerAlreadyPostedException;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makePurchasePayment(int $amount): Payment
{
    return Payment::factory()->success()->purchase()->create(['amount' => $amount]);
}

it('posts the purchase commission split between platform and inspector', function () {
    $inspector = User::factory()->inspector()->create();
    $payment = makePurchasePayment(100000000);

    app(PostPurchasePayout::class)->handle($payment, 3000000, 2700000, $inspector->id);

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();

    expect($entries)->toHaveCount(2);

    $platformEntry = $entries->firstWhere('entry_type', LedgerEntryType::PlatformFee);
    $inspectorEntry = $entries->firstWhere('entry_type', LedgerEntryType::SaleCommission);

    expect((int) $platformEntry->amount)->toBe(300000)
        ->and($platformEntry->account_user_id)->toBeNull()
        ->and((int) $inspectorEntry->amount)->toBe(2700000)
        ->and($inspectorEntry->account_user_id)->toBe($inspector->id)
        ->and($entries->sum('amount'))->toBe(3000000);
});

it('records only commissions, never the seller share', function () {
    $inspector = User::factory()->inspector()->create();
    $seller = User::factory()->user()->create();
    $payment = makePurchasePayment(100000000);

    app(PostPurchasePayout::class)->handle($payment, 3000000, 2700000, $inspector->id);

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();

    // The seller's 97.000.000 share is settled off-ledger; entries must
    // never reference the seller and must sum to the commission only.
    expect($entries->where('account_user_id', $seller->id))->toBeEmpty()
        ->and($entries->sum('amount'))->toBe(3000000)
        ->and($entries->sum('amount'))->not->toBe(100000000);
});

it('does not change the payment status', function () {
    $inspector = User::factory()->inspector()->create();
    $payment = makePurchasePayment(100000000);

    app(PostPurchasePayout::class)->handle($payment, 3000000, 2700000, $inspector->id);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Success);
});

it('writes only the platform fee entry when there is no inspector commission', function () {
    $payment = makePurchasePayment(100000000);

    app(PostPurchasePayout::class)->handle($payment, 3000000, 0, '');

    $entries = LedgerEntry::query()->where('payment_id', $payment->id)->get();

    expect($entries)->toHaveCount(1);

    $platformEntry = $entries->first();

    expect($platformEntry->entry_type)->toBe(LedgerEntryType::PlatformFee)
        ->and((int) $platformEntry->amount)->toBe(3000000)
        ->and($platformEntry->account_user_id)->toBeNull();
});

it('is a no-op when both commissions are zero', function () {
    $payment = makePurchasePayment(100000000);

    app(PostPurchasePayout::class)->handle($payment, 0, 0, '');

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
});

it('rejects non-success payments', function () {
    $inspector = User::factory()->inspector()->create();
    $payment = Payment::factory()->pending()->purchase()->create(['amount' => 100000000]);

    expect(fn () => app(PostPurchasePayout::class)->handle($payment, 3000000, 2700000, $inspector->id))
        ->toThrow(InvalidLedgerPostingException::class);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(0);
});

it('rejects payments whose purpose is not purchase', function (PaymentPurpose $purpose) {
    $inspector = User::factory()->inspector()->create();
    $payment = Payment::factory()->success()->create(['purpose' => $purpose, 'amount' => 100000000]);

    expect(fn () => app(PostPurchasePayout::class)->handle($payment, 3000000, 2700000, $inspector->id))
        ->toThrow(InvalidLedgerPostingException::class);
})->with([PaymentPurpose::Inspection, PaymentPurpose::Access]);

it('rejects negative commissions', function (int $sale, int $inspectorShare) {
    $inspector = User::factory()->inspector()->create();
    $payment = makePurchasePayment(100000000);

    expect(fn () => app(PostPurchasePayout::class)->handle($payment, $sale, $inspectorShare, $inspector->id))
        ->toThrow(InvalidLedgerPostingException::class);
})->with([
    'negative sale commission' => [-1, 0],
    'negative inspector commission' => [3000000, -1],
]);

it('rejects an inspector commission larger than the total commission', function () {
    $inspector = User::factory()->inspector()->create();
    $payment = makePurchasePayment(100000000);

    expect(fn () => app(PostPurchasePayout::class)->handle($payment, 3000000, 3000001, $inspector->id))
        ->toThrow(InvalidLedgerPostingException::class);
});

it('rejects a total commission larger than the payment amount', function () {
    $inspector = User::factory()->inspector()->create();
    $payment = makePurchasePayment(100000000);

    expect(fn () => app(PostPurchasePayout::class)->handle($payment, 100000001, 0, ''))
        ->toThrow(InvalidLedgerPostingException::class);
});

it('requires a valid inspector when the inspector commission is positive', function (?string $inspectorId) {
    $payment = makePurchasePayment(100000000);
    $nonInspector = User::factory()->user()->create();

    $id = $inspectorId === 'missing' ? '00000000-0000-0000-0000-000000000000' : $nonInspector->id;

    expect(fn () => app(PostPurchasePayout::class)->handle($payment, 3000000, 2700000, $id))
        ->toThrow(InvalidLedgerPostingException::class);
})->with(['missing', 'non-inspector user']);

it('throws LedgerAlreadyPostedException on a duplicate posting (stale model race)', function () {
    $inspector = User::factory()->inspector()->create();
    $payment = makePurchasePayment(100000000);

    // Two stale instances of the same payment, like two concurrent requests.
    $first = Payment::query()->findOrFail($payment->id);
    $second = Payment::query()->findOrFail($payment->id);

    app(PostPurchasePayout::class)->handle($first, 3000000, 2700000, $inspector->id);

    expect(fn () => app(PostPurchasePayout::class)->handle($second, 3000000, 2700000, $inspector->id))
        ->toThrow(LedgerAlreadyPostedException::class);

    expect(LedgerEntry::query()->where('payment_id', $payment->id)->count())->toBe(2);
});
