<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\ReturnListingFromTransaction;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Listeners\ReturnListingToActiveOnTransactionCancelled;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Events\TransactionCancelled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns an in_transaction listing to active', function () {
    $listing = Listing::factory()->create(['status' => ListingStatus::InTransaction]);

    $result = app(ReturnListingFromTransaction::class)->handle($listing);

    expect($result->status)->toBe(ListingStatus::Active);
    expect(Listing::query()->find($listing->getKey())->status)->toBe(ListingStatus::Active);
});

it('is idempotent when the listing is already active', function () {
    $listing = Listing::factory()->create(['status' => ListingStatus::Active]);

    $result = app(ReturnListingFromTransaction::class)->handle($listing);

    expect($result->status)->toBe(ListingStatus::Active);
});

it('leaves listings in other statuses untouched', function () {
    $listing = Listing::factory()->create(['status' => ListingStatus::InInspection]);

    $result = app(ReturnListingFromTransaction::class)->handle($listing);

    // A cancel at offered/accepted never moved the listing; nothing to undo.
    expect($result->status)->toBe(ListingStatus::InInspection);
});

it('listener returns the listing to active on TransactionCancelled', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->create([
        'seller_id' => $seller->id,
        'status' => ListingStatus::InTransaction,
    ]);

    app(ReturnListingToActiveOnTransactionCancelled::class)->handle(
        new TransactionCancelled(
            transactionId: (string) Str::uuid(),
            listingId: (string) $listing->getKey(),
            buyerId: (string) $buyer->getKey(),
            sellerId: (string) $seller->getKey(),
            cancelledBy: (string) $seller->getKey(),
        )
    );

    expect(Listing::query()->find($listing->getKey())->status)->toBe(ListingStatus::Active);
});

it('listener ignores a missing listing', function () {
    app(ReturnListingToActiveOnTransactionCancelled::class)->handle(
        new TransactionCancelled(
            transactionId: (string) Str::uuid(),
            listingId: (string) Str::uuid(),
            buyerId: (string) Str::uuid(),
            sellerId: (string) Str::uuid(),
            cancelledBy: (string) Str::uuid(),
        )
    );

    expect(true)->toBeTrue();
});
