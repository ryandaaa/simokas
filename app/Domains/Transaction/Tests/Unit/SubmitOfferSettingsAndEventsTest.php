<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Setting\Models\AppSetting;
use App\Domains\Transaction\Actions\SubmitOffer;
use App\Domains\Transaction\Events\TransactionOffered;
use App\Domains\Transaction\Exceptions\InvalidListingStatusException;
use App\Domains\Transaction\Exceptions\InvalidOfferPriceException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('TRX-T05 reads sale commission pct from Setting instead of hardcoding', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'is_inspected' => true,
    ]);

    InspectionResult::factory()->submitted()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
    ]);

    AppSetting::create(['key' => 'sale_commission_pct', 'value' => 5]);

    $transaction = app(SubmitOffer::class)->handle($buyer, $listing, $listing->price);

    expect($transaction->commission_pct)->toBe(5);
});

it('TRX-T01 rejects offers on non-offerable listing statuses', function (string $state) {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->$state()->create(['seller_id' => $seller->id]);

    expect(fn () => app(SubmitOffer::class)->handle($buyer, $listing, $listing->price))
        ->toThrow(InvalidListingStatusException::class);
})->with(['pendingReview', 'rejected', 'inTransaction', 'sold', 'inactive']);

it('rejects non-positive offer price with INVALID_OFFER_PRICE', function (int $price) {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    expect(fn () => app(SubmitOffer::class)->handle($buyer, $listing, $price))
        ->toThrow(InvalidOfferPriceException::class);
})->with([0, -1, -100000]);

it('TRX-T04 ignores draft inspection result when copying commission', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'is_inspected' => true,
    ]);

    InspectionResult::factory()->draft()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
    ]);

    $transaction = app(SubmitOffer::class)->handle($buyer, $listing, $listing->price);

    expect($transaction->commission_pct)->toBeNull()
        ->and($transaction->inspector_id)->toBeNull();
});

it('TRX-T04 picks the submitted result when a newer draft exists', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $submittedInspector = User::factory()->inspector()->create();
    $draftInspector = User::factory()->inspector()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'is_inspected' => true,
    ]);

    $template = InspectionTemplate::firstOrCreate(
        ['vehicle_type' => VehicleType::Car->value, 'version' => 1],
        ['name' => 'Template Inspeksi Mobil v1', 'is_active' => true],
    );

    InspectionResult::factory()->submitted()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $submittedInspector->id,
        'template_id' => $template->id,
        'submitted_at' => now()->subDay(),
    ]);

    InspectionResult::factory()->draft()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $draftInspector->id,
        'template_id' => $template->id,
    ]);

    $transaction = app(SubmitOffer::class)->handle($buyer, $listing, $listing->price);

    expect($transaction->commission_pct)->toBe(3)
        ->and($transaction->inspector_id)->toBe($submittedInspector->id);
});

it('dispatches TransactionOffered exactly once after commit', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    Event::fake([TransactionOffered::class]);

    $transaction = app(SubmitOffer::class)->handle($buyer, $listing, $listing->price);

    Event::assertDispatchedTimes(TransactionOffered::class, 1);
    Event::assertDispatched(TransactionOffered::class, fn (TransactionOffered $event) => $event->transactionId === (string) $transaction->getKey()
        && $event->listingId === (string) $listing->getKey()
        && $event->buyerId === (string) $buyer->getKey()
        && $event->sellerId === (string) $seller->getKey());
});
