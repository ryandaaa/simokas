<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Actions\ProcessPaymentResult;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentSimulationOutcome;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Transaction\Actions\ConfirmHandover;
use App\Domains\Transaction\Actions\OpenTransactionPayment;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionCompleted;
use App\Domains\Transaction\Exceptions\HandoverNotAvailableException;
use App\Domains\Transaction\Exceptions\UnauthorizedTransactionActionException;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makePaidTransactionForHandover(?int $commissionPct = 3): Transaction
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

it('TRX-T09 completes the handover and posts the commission ledger', function () {
    Event::fake([TransactionCompleted::class]);

    $transaction = makePaidTransactionForHandover();
    $buyer = $transaction->buyer;

    $completed = app(ConfirmHandover::class)->handle($buyer, $transaction->fresh());

    expect($completed->status)->toBe(TransactionStatus::Completed)
        ->and($completed->completed_at)->not->toBeNull();

    $entries = LedgerEntry::query()->where('payment_id', $transaction->payment_id)->get();

    expect($entries)->toHaveCount(2);

    $platformEntry = $entries->firstWhere('entry_type', LedgerEntryType::PlatformFee);
    $inspectorEntry = $entries->firstWhere('entry_type', LedgerEntryType::SaleCommission);

    expect((int) $platformEntry->amount)->toBe(300000)
        ->and((int) $inspectorEntry->amount)->toBe(2700000)
        ->and($inspectorEntry->account_user_id)->toBe((string) $transaction->inspector_id);

    Event::assertDispatchedTimes(TransactionCompleted::class, 1);
});

it('TRX-T09 moves the listing to sold when the handover is confirmed', function () {
    $transaction = makePaidTransactionForHandover();
    $buyer = $transaction->buyer;

    app(ConfirmHandover::class)->handle($buyer, $transaction->fresh());

    expect($transaction->fresh()->listing->status)->toBe(ListingStatus::Sold);
});

it('TRX-T09b writes no ledger entries when both commissions are zero', function () {
    $transaction = makePaidTransactionForHandover(null);
    $buyer = $transaction->buyer;

    app(ConfirmHandover::class)->handle($buyer, $transaction->fresh());

    expect($transaction->fresh()->status)->toBe(TransactionStatus::Completed)
        ->and(LedgerEntry::query()->where('payment_id', $transaction->payment_id)->count())->toBe(0);
});

it('lets an active admin confirm the handover', function () {
    $transaction = makePaidTransactionForHandover();
    $admin = User::factory()->admin()->create();

    $completed = app(ConfirmHandover::class)->handle($admin, $transaction->fresh());

    expect($completed->status)->toBe(TransactionStatus::Completed);
});

it('rejects handover confirmation by outsiders', function () {
    $transaction = makePaidTransactionForHandover();
    $stranger = User::factory()->user()->create();

    expect(fn () => app(ConfirmHandover::class)->handle($stranger, $transaction->fresh()))
        ->toThrow(UnauthorizedTransactionActionException::class);

    expect($transaction->fresh()->status)->toBe(TransactionStatus::PaidHeld);
});

it('rejects handover confirmation by the seller', function () {
    $transaction = makePaidTransactionForHandover();
    $seller = $transaction->listing->seller;

    expect(fn () => app(ConfirmHandover::class)->handle($seller, $transaction->fresh()))
        ->toThrow(UnauthorizedTransactionActionException::class);
});

it('TRX-T15 confirming twice has a single effect', function () {
    Event::fake([TransactionCompleted::class]);

    $transaction = makePaidTransactionForHandover();
    $buyer = $transaction->buyer;

    // Two stale instances, like a double click.
    $first = Transaction::query()->findOrFail($transaction->getKey());
    $second = Transaction::query()->findOrFail($transaction->getKey());

    app(ConfirmHandover::class)->handle($buyer, $first);
    $again = app(ConfirmHandover::class)->handle($buyer, $second);

    expect($again->status)->toBe(TransactionStatus::Completed)
        ->and(LedgerEntry::query()->where('payment_id', $transaction->payment_id)->count())->toBe(2);

    Event::assertDispatchedTimes(TransactionCompleted::class, 1);
});

it('rejects handover for a transaction that is not paid_held', function (string $state) {
    $transaction = makePaidTransactionForHandover();
    $transaction->update(['status' => $state]);
    $buyer = $transaction->buyer;

    expect(fn () => app(ConfirmHandover::class)->handle($buyer, $transaction->fresh()))
        ->toThrow(HandoverNotAvailableException::class);
})->with(['offered', 'accepted', 'rejected', 'cancelled', 'expired']);

it('TRX-T16 keeps the ledger invariant: price = seller + inspector + platform', function () {
    $transaction = makePaidTransactionForHandover();
    $buyer = $transaction->buyer;

    $completed = app(ConfirmHandover::class)->handle($buyer, $transaction->fresh());

    $entries = LedgerEntry::query()->where('payment_id', $transaction->payment_id)->get();
    $sellerNet = (int) $completed->price_amount
        - (int) $completed->inspector_commission
        - (int) $completed->platform_commission;

    expect($sellerNet + $entries->sum('amount'))->toBe((int) $completed->price_amount)
        ->and($sellerNet)->toBe(97000000);
});
