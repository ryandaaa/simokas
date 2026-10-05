<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Livewire\EditListing;
use App\Domains\Listing\Livewire\SellerListings;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('LST-T05 shows the rejection reason and resubmit button on my listings page', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->rejected('Foto buram, mohon perbaiki.')->create([
        'seller_id' => $seller->id,
    ]);
    actingAs($seller);

    Livewire::test(SellerListings::class)
        ->assertSee('Ditolak')
        ->assertSee('Foto buram, mohon perbaiki.')
        ->assertSee(__('listing.mine.resubmit'));
});

it('LST-T05 seller resubmits a rejected listing from my listings page', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->rejected('Foto buram, mohon perbaiki.')->create([
        'seller_id' => $seller->id,
    ]);
    actingAs($seller);

    Livewire::test(SellerListings::class)
        ->call('confirmResubmit', $listing->id)
        ->assertSet('confirmingResubmitId', $listing->id)
        ->call('resubmit')
        ->assertSet('confirmingResubmitId', null)
        ->assertSee(__('listing.mine.resubmitted_success'));

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::PendingReview)
        ->and($fresh->rejection_reason)->toBeNull();
});

it('forbids resubmission of another seller listing', function () {
    $seller = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    $listing = Listing::factory()->rejected('Foto buram.')->create([
        'seller_id' => $other->id,
    ]);
    actingAs($seller);

    Livewire::test(SellerListings::class)
        ->call('confirmResubmit', $listing->id)
        ->call('resubmit')
        ->assertForbidden();

    expect($listing->fresh()->status)->toBe(ListingStatus::Rejected);
});

it('shows the rejection reason on the edit page for rejected listings', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->rejected('Nomor rangka tidak jelas.')->create([
        'seller_id' => $seller->id,
    ]);
    actingAs($seller);

    get(route('listings.edit', $listing))
        ->assertOk()
        ->assertSee('Nomor rangka tidak jelas.')
        ->assertSee(__('listing.mine.resubmit'));
});

it('seller resubmits a rejected listing from the edit page', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->rejected('Nomor rangka tidak jelas.')->create([
        'seller_id' => $seller->id,
    ]);

    Livewire::actingAs($seller)
        ->test(EditListing::class, ['listing' => $listing])
        ->call('resubmit')
        ->assertRedirect(route('listings.mine'));

    expect($listing->fresh()->status)->toBe(ListingStatus::PendingReview);
});
