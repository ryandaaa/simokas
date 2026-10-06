<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Transaction\Actions\SubmitOffer;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Exceptions\InspectorCannotBuyException;
use App\Domains\Transaction\Exceptions\InvalidListingStatusException;
use App\Domains\Transaction\Exceptions\OwnListingOfferException;
use App\Domains\Transaction\Exceptions\TransactionAlreadyRunningException;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('TRX-T01 allows buyer to submit offer on active listing', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $action = app(SubmitOffer::class);
    $transaction = $action->handle($buyer, $listing, $listing->price);

    expect($transaction)->not->toBeNull();
    expect($transaction->status)->toBe(TransactionStatus::Offered);
    expect($transaction->buyer_id)->toBe($buyer->id);
    expect($transaction->listing_id)->toBe($listing->id);
    expect($transaction->price_amount)->toBe($listing->price);
    expect($transaction->commission_pct)->toBeNull();
    expect($transaction->inspector_id)->toBeNull();
    expect($transaction->expires_at)->not->toBeNull();
});

it('TRX-T01 allows buyer to submit offer on in_inspection listing', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

    $action = app(SubmitOffer::class);
    $transaction = $action->handle($buyer, $listing, $listing->price);

    expect($transaction)->not->toBeNull();
    expect($transaction->status)->toBe(TransactionStatus::Offered);
    expect($transaction->buyer_id)->toBe($buyer->id);
});

it('TRX-T01 rejects offer on rejected listing', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->rejected()->create(['seller_id' => $seller->id]);

    $action = app(SubmitOffer::class);

    expect(fn () => $action->handle($buyer, $listing, $listing->price))
        ->toThrow(InvalidListingStatusException::class);
});

it('TRX-T01 rejects offer on pending_review listing', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->pendingReview()->create(['seller_id' => $seller->id]);

    $action = app(SubmitOffer::class);

    expect(fn () => $action->handle($buyer, $listing, $listing->price))
        ->toThrow(InvalidListingStatusException::class);
});

it('TRX-T01 rejects offer on sold listing', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->sold()->create(['seller_id' => $seller->id]);

    $action = app(SubmitOffer::class);

    expect(fn () => $action->handle($buyer, $listing, $listing->price))
        ->toThrow(InvalidListingStatusException::class);
});

it('TRX-T02 rejects offer when buyer is listing owner', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $action = app(SubmitOffer::class);

    expect(fn () => $action->handle($seller, $listing, $listing->price))
        ->toThrow(OwnListingOfferException::class);
});

it('TRX-T02 rejects offer when buyer is inspector', function () {
    $seller = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $action = app(SubmitOffer::class);

    expect(fn () => $action->handle($inspector, $listing, $listing->price))
        ->toThrow(InspectorCannotBuyException::class);
});

it('TRX-T03 allows only one active transaction per listing', function () {
    $seller = User::factory()->user()->create();
    $buyer1 = User::factory()->user()->create();
    $buyer2 = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $action = app(SubmitOffer::class);

    // First buyer successfully submits offer
    $tx1 = $action->handle($buyer1, $listing, $listing->price);
    expect($tx1)->not->toBeNull();
    expect($tx1->status)->toBe(TransactionStatus::Offered);

    // Second buyer tries to submit - should fail with 409
    expect(fn () => $action->handle($buyer2, $listing, $listing->price))
        ->toThrow(TransactionAlreadyRunningException::class);
});

it('TRX-T04 includes commission details when listing has inspection result', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();

    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'is_inspected' => true,
        'price' => 100000000,
    ]);

    // Create inspection result
    InspectionResult::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'status' => ResultStatus::Submitted,
    ]);

    $action = app(SubmitOffer::class);
    $transaction = $action->handle($buyer, $listing, $listing->price);

    expect($transaction)->not->toBeNull();
    expect($transaction->commission_pct)->toBe(3);
    expect($transaction->inspector_id)->toBe($inspector->id);
});

it('TRX-T04 has no commission when listing has no inspection result', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'is_inspected' => false,
    ]);

    $action = app(SubmitOffer::class);
    $transaction = $action->handle($buyer, $listing, $listing->price);

    expect($transaction)->not->toBeNull();
    expect($transaction->commission_pct)->toBeNull();
    expect($transaction->inspector_id)->toBeNull();
});

it('TRX-T05 copies price and does not change when listing price changes after offer', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'price' => 100000000,
    ]);

    $action = app(SubmitOffer::class);
    $transaction = $action->handle($buyer, $listing, $listing->price);

    expect($transaction->price_amount)->toBe(100000000);

    // Change listing price
    $listing->update(['price' => 150000000]);

    // Transaction price should remain unchanged
    expect($transaction->refresh()->price_amount)->toBe(100000000);
});

it('TRX-T05 copies commission percent and does not change when settings change', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();

    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'is_inspected' => true,
    ]);

    InspectionResult::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'status' => ResultStatus::Submitted,
    ]);

    $action = app(SubmitOffer::class);
    $transaction = $action->handle($buyer, $listing, $listing->price);

    expect($transaction->commission_pct)->toBe(3);

    // Even if settings change, transaction should keep original
    expect($transaction->refresh()->commission_pct)->toBe(3);
});

it('TRX-T05 copies inspector ID and does not change when new result is added', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $inspector1 = User::factory()->inspector()->create();
    $inspector2 = User::factory()->inspector()->create();

    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'is_inspected' => true,
    ]);

    $result1 = InspectionResult::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector1->id,
        'status' => ResultStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $action = app(SubmitOffer::class);
    $transaction = $action->handle($buyer, $listing, $listing->price);

    expect($transaction->inspector_id)->toBe($inspector1->id);

    // Add another result from different inspector
    InspectionResult::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector2->id,
        'status' => ResultStatus::Submitted,
        'submitted_at' => now()->addDay(),
    ]);

    // Transaction should still reference original inspector
    expect($transaction->refresh()->inspector_id)->toBe($inspector1->id);
});
