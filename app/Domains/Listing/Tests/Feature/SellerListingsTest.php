<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Livewire\SellerListings;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('displays seller listings with status chip and formatted details', function () {
    $seller = User::factory()->user()->create();
    $otherSeller = User::factory()->user()->create();

    $myListing = Listing::factory()->pendingReview()->create([
        'seller_id' => $seller->id,
        'title' => 'Mobil Saya Toyota Avanza',
        'price' => 120_000_000,
    ]);

    $otherListing = Listing::factory()->active()->create([
        'seller_id' => $otherSeller->id,
        'title' => 'Mobil Orang Lain Honda Jazz',
    ]);

    actingAs($seller);

    Livewire::test(SellerListings::class)
        ->assertSee('Mobil Saya Toyota Avanza')
        ->assertSee('Menunggu Validasi')
        ->assertDontSee('Mobil Orang Lain Honda Jazz');
});

it('displays empty state when seller has no listings', function () {
    $seller = User::factory()->user()->create();
    actingAs($seller);

    Livewire::test(SellerListings::class)
        ->assertSee('Belum ada kendaraan yang dipasang')
        ->assertSee('Tambah Barang');
});

it('forbids guests and inspectors from viewing seller listings page', function () {
    // Guest
    get(route('listings.mine'))->assertRedirect(route('login'));

    // Inspector
    $inspector = User::factory()->inspector()->create();
    actingAs($inspector);
    get(route('listings.mine'))->assertForbidden();
});
