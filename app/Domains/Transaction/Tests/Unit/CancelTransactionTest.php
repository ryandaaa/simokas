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
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Transaction\Actions\ApproveCancellationRequest;
use App\Domains\Transaction\Actions\CancelTransaction;
use App\Domains\Transaction\Actions\ConfirmHandover;
use App\Domains\Transaction\Actions\OpenTransactionPayment;
use App\Domains\Transaction\Actions\RequestTransactionCancellation;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionCancelled;
use App\Domains\Transaction\Exceptions\HandoverNotAvailableException;
use App\Domains\Transaction\Exceptions\TransactionNotCancellableException;
use App\Domains\Transaction\Exceptions\UnauthorizedTransactionActionException;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return array{transaction: Transaction, buyer: User, seller: User, listing: Listing}
 */
function makeTransactionForCancel(TransactionStatus $status): array
{
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $state = match ($status) {
        TransactionStatus::Offered => 'offered',
        TransactionStatus::Accepted => 'accepted',
        default => 'accepted',
    };

    $transaction = Transaction::factory()->$state()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'price_amount' => 100000000,
        'commission_pct' => 3,
        'inspector_id' => User::factory()->inspector()->create()->id,
    ]);

    if ($status === TransactionStatus::PaidHeld) {
        $bill = app(OpenTransactionPayment::class)->handle($buyer, $transaction);
        $payment = Payment::query()->findOrFail($bill->id);
        app(ProcessPaymentResult::class)->handle($payment, PaymentMethod::Qris, PaymentSimulationOutcome::Success);
        $transaction = $transaction->fresh();
    }

    return [
        'transaction' => $transaction->fresh(),
        'buyer' => $buyer,
        'seller' => $seller,
        'listing' => $listing->fresh(),
    ];
}

it('TRX-T10 buyer cancels an offered transaction freely', function () {
    ['transaction' => $transaction, 'buyer' => $buyer, 'listing' => $listing] = makeTransactionForCancel(TransactionStatus::Offered);

    Event::fake([TransactionCancelled::class]);

    $result = app(CancelTransaction::class)->handle($buyer, $transaction);

    expect($result->status)->toBe(TransactionStatus::Cancelled)
        ->and($result->cancelled_by)->toBe((string) $buyer->getKey());

    // Listing untouched: never moved to in_transaction.
    expect($listing->fresh()->status)->toBe(ListingStatus::Active);

    // No money moved.
    expect(LedgerEntry::query()->count())->toBe(0);

    Event::assertDispatched(TransactionCancelled::class, 1);
});

it('TRX-T10 seller cannot cancel an offered transaction', function () {
    ['transaction' => $transaction, 'seller' => $seller] = makeTransactionForCancel(TransactionStatus::Offered);

    expect(fn () => app(CancelTransaction::class)->handle($seller, $transaction))
        ->toThrow(UnauthorizedTransactionActionException::class);

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Offered);
});

it('TRX-T10 buyer and seller cancel an accepted transaction freely', function () {
    foreach (['buyer', 'seller'] as $role) {
        ['transaction' => $transaction, $role => $actor, 'listing' => $listing] = makeTransactionForCancel(TransactionStatus::Accepted);

        $result = app(CancelTransaction::class)->handle($actor, $transaction, 'changed my mind');

        expect($result->status)->toBe(TransactionStatus::Cancelled)
            ->and($result->cancel_reason)->toBe('changed my mind');

        expect($listing->fresh()->status)->toBe(ListingStatus::Active);
        expect(LedgerEntry::query()->count())->toBe(0);
    }
});

it('TRX-T10 seller cancelling paid_held refunds 100% and returns listing to active', function () {
    ['transaction' => $transaction, 'seller' => $seller, 'listing' => $listing] = makeTransactionForCancel(TransactionStatus::PaidHeld);

    expect($listing->status)->toBe(ListingStatus::InTransaction);

    $result = app(CancelTransaction::class)->handle($seller, $transaction, 'unit rusak');

    expect($result->status)->toBe(TransactionStatus::Cancelled)
        ->and($result->cancel_reason)->toBe('unit rusak')
        ->and($result->cancelled_by)->toBe((string) $seller->getKey());

    // Exactly one refund entry; commission never posted (T3).
    $entries = LedgerEntry::query()->where('payment_id', $result->payment_id)->get();
    expect($entries)->toHaveCount(1)
        ->and($entries->first()->entry_type)->toBe(LedgerEntryType::Refund)
        ->and(LedgerEntry::query()->whereIn('entry_type', [
            LedgerEntryType::SaleCommission->value,
            LedgerEntryType::PlatformFee->value,
        ])->count())->toBe(0);

    expect(Payment::query()->find($result->payment_id)->status)->toBe(PaymentStatus::Refunded);

    // Listing back to active via the shared listener.
    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('TRX-T10 cancelling dispatches TransactionCancelled exactly once', function () {
    ['transaction' => $transaction, 'seller' => $seller] = makeTransactionForCancel(TransactionStatus::PaidHeld);

    Event::fake([TransactionCancelled::class]);

    app(CancelTransaction::class)->handle($seller, $transaction, 'unit rusak');

    Event::assertDispatched(TransactionCancelled::class, 1);

    // Second (idempotent) cancel dispatches nothing more.
    app(CancelTransaction::class)->handle($seller, $transaction->fresh(), 'unit rusak');

    Event::assertDispatched(TransactionCancelled::class, 1);
});

it('TRX-T10 seller cancelling paid_held without reason is rejected', function () {
    ['transaction' => $transaction, 'seller' => $seller] = makeTransactionForCancel(TransactionStatus::PaidHeld);

    expect(fn () => app(CancelTransaction::class)->handle($seller, $transaction))
        ->toThrow(TransactionNotCancellableException::class);

    expect(fn () => app(CancelTransaction::class)->handle($seller, $transaction, '   '))
        ->toThrow(TransactionNotCancellableException::class);

    expect($transaction->fresh()->status)->toBe(TransactionStatus::PaidHeld);
});

it('TRX-T10 buyer cannot cancel paid_held directly', function () {
    ['transaction' => $transaction, 'buyer' => $buyer] = makeTransactionForCancel(TransactionStatus::PaidHeld);

    expect(fn () => app(CancelTransaction::class)->handle($buyer, $transaction, 'mau batal'))
        ->toThrow(UnauthorizedTransactionActionException::class);

    expect($transaction->fresh()->status)->toBe(TransactionStatus::PaidHeld);
});

it('TRX-T10 buyer requests cancellation on paid_held (idempotent)', function () {
    ['transaction' => $transaction, 'buyer' => $buyer] = makeTransactionForCancel(TransactionStatus::PaidHeld);

    $result = app(RequestTransactionCancellation::class)->handle($buyer, $transaction);

    expect($result->status)->toBe(TransactionStatus::PaidHeld)
        ->and($result->cancel_requested_at)->not->toBeNull();

    $first = $result->cancel_requested_at;

    // Second request keeps the first timestamp.
    $again = app(RequestTransactionCancellation::class)->handle($buyer, $result->fresh());

    expect($again->cancel_requested_at->equalTo($first))->toBeTrue();
    expect(LedgerEntry::query()->count())->toBe(0);
});

it('TRX-T10 seller approves the buyer request with the same outcome as unilateral cancel', function () {
    ['transaction' => $transaction, 'buyer' => $buyer, 'seller' => $seller, 'listing' => $listing] = makeTransactionForCancel(TransactionStatus::PaidHeld);

    app(RequestTransactionCancellation::class)->handle($buyer, $transaction);

    $result = app(ApproveCancellationRequest::class)->handle($seller, $transaction->fresh(), 'setuju batal');

    expect($result->status)->toBe(TransactionStatus::Cancelled);

    $entries = LedgerEntry::query()->where('payment_id', $result->payment_id)->get();
    expect($entries)->toHaveCount(1)
        ->and($entries->first()->entry_type)->toBe(LedgerEntryType::Refund);

    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('TRX-T10 active admin cancels paid_held with refund', function () {
    ['transaction' => $transaction, 'listing' => $listing] = makeTransactionForCancel(TransactionStatus::PaidHeld);
    $admin = User::factory()->admin()->create();

    $result = app(CancelTransaction::class)->handle($admin, $transaction, 'keputusan admin');

    expect($result->status)->toBe(TransactionStatus::Cancelled)
        ->and(Payment::query()->find($result->payment_id)->status)->toBe(PaymentStatus::Refunded);

    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('TRX-T10 cancelling twice has a single effect (stale model)', function () {
    ['transaction' => $transaction, 'buyer' => $buyer] = makeTransactionForCancel(TransactionStatus::Accepted);

    $stale = Transaction::query()->findOrFail($transaction->getKey());

    app(CancelTransaction::class)->handle($buyer, $transaction);

    // Second call with the stale instance: idempotent, no duplicate event.
    Event::fake([TransactionCancelled::class]);

    $result = app(CancelTransaction::class)->handle($buyer, $stale);

    expect($result->status)->toBe(TransactionStatus::Cancelled);
    expect(LedgerEntry::query()->count())->toBe(0);

    Event::assertNotDispatched(TransactionCancelled::class);
});

it('TRX-T10 cancel vs handover race at paid_held has exactly one winner', function () {
    ['transaction' => $transaction, 'buyer' => $buyer, 'seller' => $seller] = makeTransactionForCancel(TransactionStatus::PaidHeld);

    $staleForHandover = Transaction::query()->findOrFail($transaction->getKey());

    // Cancel wins first.
    app(CancelTransaction::class)->handle($seller, $transaction, 'batal dulu');

    // Handover on the stale model must fail.
    expect(fn () => app(ConfirmHandover::class)->handle($buyer, $staleForHandover))
        ->toThrow(HandoverNotAvailableException::class);

    // Ledger consistent: only the refund, no commission.
    expect(LedgerEntry::query()->where('entry_type', LedgerEntryType::Refund->value)->count())->toBe(1);
    expect(LedgerEntry::query()->whereIn('entry_type', [
        LedgerEntryType::SaleCommission->value,
        LedgerEntryType::PlatformFee->value,
    ])->count())->toBe(0);
});

it('TRX-T10 late payment after accepted-cancel is refunded automatically', function () {
    ['transaction' => $transaction, 'buyer' => $buyer] = makeTransactionForCancel(TransactionStatus::Accepted);

    // Bill opened before cancel...
    $bill = app(OpenTransactionPayment::class)->handle($buyer, $transaction);
    $paymentId = $bill->id;

    // ...then buyer cancels while accepted.
    app(CancelTransaction::class)->handle($buyer, $transaction->fresh());

    // Payment arrives late: the existing listener refunds it, untouched.
    $payment = Payment::query()->findOrFail($paymentId);
    app(ProcessPaymentResult::class)->handle($payment, PaymentMethod::Qris, PaymentSimulationOutcome::Success);

    expect(Payment::query()->find($paymentId)->status)->toBe(PaymentStatus::Refunded);
    expect(Transaction::query()->find($transaction->getKey())->status)->toBe(TransactionStatus::Cancelled);
});

it('TRX-T10 final statuses cannot be cancelled', function () {
    foreach ([TransactionStatus::Completed, TransactionStatus::Rejected, TransactionStatus::Expired] as $status) {
        ['transaction' => $transaction, 'buyer' => $buyer] = makeTransactionForCancel(TransactionStatus::Offered);

        Transaction::query()->whereKey($transaction->getKey())->update(['status' => $status->value]);

        expect(fn () => app(CancelTransaction::class)->handle($buyer, $transaction->fresh()))
            ->toThrow(TransactionNotCancellableException::class);
    }
});

it('TRX-T10 cancelling an already-cancelled transaction is a harmless no-op', function () {
    ['transaction' => $transaction, 'buyer' => $buyer] = makeTransactionForCancel(TransactionStatus::Offered);

    Transaction::query()->whereKey($transaction->getKey())->update(['status' => TransactionStatus::Cancelled->value]);

    Event::fake([TransactionCancelled::class]);

    $result = app(CancelTransaction::class)->handle($buyer, $transaction->fresh());

    expect($result->status)->toBe(TransactionStatus::Cancelled);

    Event::assertNotDispatched(TransactionCancelled::class);
});

it('TRX-T13 status transitions accept valid pairs and reject others', function () {
    expect(TransactionStatus::Offered->canTransitionTo(TransactionStatus::Cancelled))->toBeTrue();
    expect(TransactionStatus::Offered->canTransitionTo(TransactionStatus::Expired))->toBeTrue();
    expect(TransactionStatus::Accepted->canTransitionTo(TransactionStatus::Cancelled))->toBeTrue();
    expect(TransactionStatus::Accepted->canTransitionTo(TransactionStatus::Expired))->toBeTrue();
    expect(TransactionStatus::PaidHeld->canTransitionTo(TransactionStatus::Cancelled))->toBeTrue();
    expect(TransactionStatus::PaidHeld->canTransitionTo(TransactionStatus::Completed))->toBeTrue();

    expect(TransactionStatus::Cancelled->canTransitionTo(TransactionStatus::Offered))->toBeFalse();
    expect(TransactionStatus::Expired->canTransitionTo(TransactionStatus::Accepted))->toBeFalse();
    expect(TransactionStatus::Completed->canTransitionTo(TransactionStatus::Cancelled))->toBeFalse();
    expect(TransactionStatus::PaidHeld->canTransitionTo(TransactionStatus::Expired))->toBeFalse();
});
