<?php

declare(strict_types=1);

use App\Domains\Listing\Actions\MarkListingInTransaction;
use App\Domains\Listing\Actions\MarkListingSold;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Listeners\MarkListingInTransactionOnTransactionPaid;
use App\Domains\Listing\Listeners\MarkListingSoldOnTransactionCompleted;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Events\TransactionCompleted;
use App\Domains\Transaction\Events\TransactionPaid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('TRX-T08 marks an active or in_inspection listing in_transaction and stays idempotent', function (string $state) {
    $listing = Listing::factory()->$state()->create();

    $result = app(MarkListingInTransaction::class)->handle($listing);
    expect($result->fresh()->status)->toBe(ListingStatus::InTransaction);

    $again = app(MarkListingInTransaction::class)->handle($listing->fresh());
    expect($again->fresh()->status)->toBe(ListingStatus::InTransaction);
})->with(['active', 'inInspection']);

it('refuses to mark a non-payable listing in_transaction', function (string $state) {
    $listing = Listing::factory()->$state()->create();

    expect(fn () => app(MarkListingInTransaction::class)->handle($listing))
        ->toThrow(InvalidListingStatusTransitionException::class);
})->with(['pendingReview', 'sold', 'inactive']);

it('TRX-T09 marks an in_transaction listing sold and stays idempotent', function () {
    $listing = Listing::factory()->inTransaction()->create();

    $result = app(MarkListingSold::class)->handle($listing);
    expect($result->fresh()->status)->toBe(ListingStatus::Sold);

    $again = app(MarkListingSold::class)->handle($listing->fresh());
    expect($again->fresh()->status)->toBe(ListingStatus::Sold);
});

it('refuses to mark a non-transaction listing sold', function (string $state) {
    $listing = Listing::factory()->$state()->create();

    expect(fn () => app(MarkListingSold::class)->handle($listing))
        ->toThrow(InvalidListingStatusTransitionException::class);
})->with(['active', 'inInspection', 'pendingReview']);

it('reacts to TransactionPaid by marking the listing in_transaction', function () {
    $listing = Listing::factory()->active()->create();

    app(MarkListingInTransactionOnTransactionPaid::class)->handle(new TransactionPaid(
        transactionId: 't1',
        listingId: (string) $listing->getKey(),
        buyerId: 'b1',
        sellerId: 's1',
        paymentId: 'p1',
    ));

    expect($listing->fresh()->status)->toBe(ListingStatus::InTransaction);
});

it('reacts to TransactionCompleted by marking the listing sold', function () {
    $listing = Listing::factory()->inTransaction()->create();

    app(MarkListingSoldOnTransactionCompleted::class)->handle(new TransactionCompleted(
        transactionId: 't1',
        listingId: (string) $listing->getKey(),
        buyerId: 'b1',
        sellerId: 's1',
    ));

    expect($listing->fresh()->status)->toBe(ListingStatus::Sold);
});

it('ignores transaction events for unknown listings', function () {
    app(MarkListingInTransactionOnTransactionPaid::class)->handle(new TransactionPaid(
        transactionId: 't1',
        listingId: '00000000-0000-0000-0000-000000000000',
        buyerId: 'b1',
        sellerId: 's1',
        paymentId: 'p1',
    ));

    app(MarkListingSoldOnTransactionCompleted::class)->handle(new TransactionCompleted(
        transactionId: 't1',
        listingId: '00000000-0000-0000-0000-000000000000',
        buyerId: 'b1',
        sellerId: 's1',
    ));

    expect(true)->toBeTrue();
});
