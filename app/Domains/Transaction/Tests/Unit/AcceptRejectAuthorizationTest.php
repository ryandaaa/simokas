<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Actions\AcceptOffer;
use App\Domains\Transaction\Actions\RejectOffer;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionAccepted;
use App\Domains\Transaction\Events\TransactionRejected;
use App\Domains\Transaction\Exceptions\OfferNotPendingException;
use App\Domains\Transaction\Exceptions\UnauthorizedTransactionActionException;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('TRX-T06 rejects accept from buyer or third party with 403', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $stranger = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->offered()->for($listing, 'listing')->create(['buyer_id' => $buyer->id]);

    expect(fn () => app(AcceptOffer::class)->handle($buyer, $transaction))
        ->toThrow(UnauthorizedTransactionActionException::class);

    expect(fn () => app(AcceptOffer::class)->handle($stranger, $transaction))
        ->toThrow(UnauthorizedTransactionActionException::class);

    try {
        app(AcceptOffer::class)->handle($buyer, $transaction);
        $this->fail('Expected UnauthorizedTransactionActionException');
    } catch (UnauthorizedTransactionActionException $e) {
        expect($e->getStatusCode())->toBe(403)
            ->and($e->getErrorCode())->toBe('TRANSACTION_FORBIDDEN');
    }

    expect($transaction->refresh()->status)->toBe(TransactionStatus::Offered);
});

it('TRX-T06 rejects reject from buyer or third party with 403', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $stranger = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->offered()->for($listing, 'listing')->create(['buyer_id' => $buyer->id]);

    expect(fn () => app(RejectOffer::class)->handle($buyer, $transaction))
        ->toThrow(UnauthorizedTransactionActionException::class);

    expect(fn () => app(RejectOffer::class)->handle($stranger, $transaction))
        ->toThrow(UnauthorizedTransactionActionException::class);

    expect($transaction->refresh()->status)->toBe(TransactionStatus::Offered);
});

it('TRX-T06 accept twice has a single effect and dispatches TransactionAccepted once', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->offered()->for($listing, 'listing')->create();

    // Two instances of the same row, like two concurrent requests.
    $first = Transaction::find($transaction->id);
    $second = Transaction::find($transaction->id);

    Event::fake([TransactionAccepted::class]);

    $result = app(AcceptOffer::class)->handle($seller, $first);
    expect($result->status)->toBe(TransactionStatus::Accepted);

    // Stale model: status in memory is still offered, but the row changed.
    $again = app(AcceptOffer::class)->handle($seller, $second);
    expect($again->status)->toBe(TransactionStatus::Accepted);

    Event::assertDispatchedTimes(TransactionAccepted::class, 1);
    Event::assertDispatched(TransactionAccepted::class, fn (TransactionAccepted $event) => $event->transactionId === (string) $transaction->getKey()
        && $event->listingId === (string) $listing->getKey());
});

it('TRX-T06 accept vs reject race has exactly one winner', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->offered()->for($listing, 'listing')->create();

    $accepter = Transaction::find($transaction->id);
    $rejecter = Transaction::find($transaction->id);

    Event::fake([TransactionAccepted::class, TransactionRejected::class]);

    app(AcceptOffer::class)->handle($seller, $accepter);

    expect(fn () => app(RejectOffer::class)->handle($seller, $rejecter))
        ->toThrow(OfferNotPendingException::class);

    expect(Transaction::find($transaction->id)->status)->toBe(TransactionStatus::Accepted);
    Event::assertDispatchedTimes(TransactionAccepted::class, 1);
    Event::assertDispatchedTimes(TransactionRejected::class, 0);
});

it('TRX-T06 throws OFFER_NOT_PENDING when accepting a non-offered transaction', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->rejected()->for($listing, 'listing')->create();

    try {
        app(AcceptOffer::class)->handle($seller, $transaction);
        $this->fail('Expected OfferNotPendingException');
    } catch (OfferNotPendingException $e) {
        expect($e->getStatusCode())->toBe(409)
            ->and($e->getErrorCode())->toBe('OFFER_NOT_PENDING');
    }
});

it('TRX-T06 reject twice has a single effect and dispatches TransactionRejected once', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->offered()->for($listing, 'listing')->create();

    $first = Transaction::find($transaction->id);
    $second = Transaction::find($transaction->id);

    Event::fake([TransactionRejected::class]);

    $result = app(RejectOffer::class)->handle($seller, $first);
    expect($result->status)->toBe(TransactionStatus::Rejected);

    $again = app(RejectOffer::class)->handle($seller, $second);
    expect($again->status)->toBe(TransactionStatus::Rejected);

    Event::assertDispatchedTimes(TransactionRejected::class, 1);
});

it('TRX-T06 throws OFFER_NOT_PENDING when rejecting an already accepted transaction', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->accepted()->for($listing, 'listing')->create();

    expect(fn () => app(RejectOffer::class)->handle($seller, $transaction))
        ->toThrow(OfferNotPendingException::class);
});
