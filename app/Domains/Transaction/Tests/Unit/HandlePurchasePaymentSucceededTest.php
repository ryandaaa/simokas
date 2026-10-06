<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Actions\ProcessPaymentResult;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentSimulationOutcome;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Setting\Enums\SettingKey;
use App\Domains\Setting\Models\AppSetting;
use App\Domains\Transaction\Actions\OpenTransactionPayment;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionPaid;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makePaidTransaction(?int $commissionPct = 3): Transaction
{
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $inspectorId = $commissionPct === null ? null : User::factory()->inspector()->create()->id;

    $transaction = Transaction::factory()->accepted()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'price_amount' => 100000000,
        'commission_pct' => $commissionPct,
        'inspector_id' => $inspectorId,
    ]);

    $bill = app(OpenTransactionPayment::class)->handle($buyer, $transaction);
    $payment = Payment::query()->findOrFail($bill->id);

    app(ProcessPaymentResult::class)->handle($payment, PaymentMethod::Qris, PaymentSimulationOutcome::Success);

    return $transaction->fresh();
}

it('TRX-T08 moves an accepted transaction to paid_held with the commission snapshot', function () {
    $transaction = makePaidTransaction();

    $fresh = $transaction->fresh();

    expect($fresh->status)->toBe(TransactionStatus::PaidHeld)
        ->and((int) $fresh->inspector_commission)->toBe(2700000)
        ->and((int) $fresh->platform_commission)->toBe(300000);
});

it('dispatches TransactionPaid exactly once after commit', function () {
    Event::fake([TransactionPaid::class]);

    $transaction = makePaidTransaction();

    Event::assertDispatchedTimes(TransactionPaid::class, 1);
    Event::assertDispatched(TransactionPaid::class, fn (TransactionPaid $event) => $event->transactionId === (string) $transaction->getKey()
        && $event->listingId === (string) $transaction->listing_id
        && $event->paymentId === (string) $transaction->payment_id);
});

it('moves the listing to in_transaction when the payment succeeds', function () {
    $transaction = makePaidTransaction();

    expect($transaction->fresh()->listing->status)->toBe(ListingStatus::InTransaction);
});

it('snapshots the platform fee pct at pay time, not the current setting', function () {
    AppSetting::updateOrCreate(['key' => SettingKey::PlatformFeePct->value], ['value' => 25]);

    $transaction = makePaidTransaction();
    $fresh = $transaction->fresh();

    expect((int) $fresh->inspector_commission)->toBe(2250000)
        ->and((int) $fresh->platform_commission)->toBe(750000);
});

it('snapshots zero commissions when the listing was never inspected', function () {
    $transaction = makePaidTransaction(null);
    $fresh = $transaction->fresh();

    expect($fresh->status)->toBe(TransactionStatus::PaidHeld)
        ->and((int) $fresh->inspector_commission)->toBe(0)
        ->and((int) $fresh->platform_commission)->toBe(0);
});

it('TRX-T08b refunds a payment that arrives after the transaction left accepted', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $transaction = Transaction::factory()->accepted()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'price_amount' => 100000000,
    ]);

    $bill = app(OpenTransactionPayment::class)->handle($buyer, $transaction);
    $payment = Payment::query()->findOrFail($bill->id);

    // The transaction expired before the payment completed.
    $transaction->update(['status' => TransactionStatus::Expired]);

    app(ProcessPaymentResult::class)->handle($payment, PaymentMethod::Qris, PaymentSimulationOutcome::Success);

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Expired)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Refunded);

    $refund = LedgerEntry::query()
        ->where('payment_id', $payment->id)
        ->where('entry_type', LedgerEntryType::Refund->value)
        ->firstOrFail();

    expect((int) $refund->amount)->toBe(100000000)
        ->and($refund->account_user_id)->toBe((string) $buyer->getKey());
});

it('ignores duplicate payment-success deliveries', function () {
    Event::fake([TransactionPaid::class]);

    $transaction = makePaidTransaction();
    $payment = Payment::query()->findOrFail($transaction->payment_id);

    // Simulate a redelivered success (already success, so dispatch the event directly).
    event(new PaymentSucceeded(
        paymentId: $payment->id,
        purpose: $payment->purpose,
        refId: $payment->ref_id,
        amount: (int) $payment->amount,
        payerId: $payment->payer_id,
    ));

    expect($transaction->fresh()->status)->toBe(TransactionStatus::PaidHeld);

    Event::assertDispatchedTimes(TransactionPaid::class, 1);
});

it('ignores purchase payments not opened for any transaction', function () {
    $payment = Payment::factory()->success()->purchase()->create(['amount' => 100000000]);

    event(new PaymentSucceeded(
        paymentId: $payment->id,
        purpose: $payment->purpose,
        refId: 'not-a-transaction',
        amount: (int) $payment->amount,
        payerId: $payment->payer_id,
    ));

    // No crash, no transaction touched.
    expect(Transaction::query()->where('status', TransactionStatus::PaidHeld->value)->count())->toBe(0);
});
