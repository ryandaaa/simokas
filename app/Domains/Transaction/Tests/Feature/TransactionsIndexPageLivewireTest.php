<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Livewire\TransactionsIndexPage;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('redirects guest to login when accessing the transactions index', function () {
    get(route('transactions.index'))->assertRedirect(route('login'));
});

it('shows only the user\u2019s own transactions as buyer and as seller', function () {
    $me = User::factory()->user()->create();
    $otherSeller = User::factory()->user()->create();
    $otherBuyer = User::factory()->user()->create();

    $myListing = Listing::factory()->active()->create(['seller_id' => $me->id, 'title' => 'Mobil saya dijual']);
    $theirListing = Listing::factory()->active()->create(['seller_id' => $otherSeller->id, 'title' => 'Mobil orang lain']);

    // I am the seller here.
    Transaction::factory()->offered()->for($myListing, 'listing')->create(['buyer_id' => $otherBuyer->id]);
    // I am the buyer here.
    Transaction::factory()->offered()->for($theirListing, 'listing')->create(['buyer_id' => $me->id]);
    // Someone else's transaction: must not appear.
    $unrelatedListing = Listing::factory()->active()->create(['title' => 'Transaksi orang lain XYZ']);
    $unrelated = Transaction::factory()->offered()->for($unrelatedListing, 'listing')->create();

    actingAs($me);

    Livewire::test(TransactionsIndexPage::class)
        ->assertOk()
        ->assertSee('Mobil saya dijual')
        ->assertSee('Mobil orang lain')
        ->assertDontSee('Transaksi orang lain XYZ');
});

it('shows an empty state when the user has no transactions', function () {
    $me = User::factory()->user()->create();

    actingAs($me);

    Livewire::test(TransactionsIndexPage::class)
        ->assertOk()
        ->assertSee(__('transaction.index.empty_title'));
});

it('keeps the transaction list query count flat as items grow', function () {
    $me = User::factory()->user()->create();
    $seller = User::factory()->user()->create();

    $listings = Listing::factory()->active()->count(3)->create(['seller_id' => $seller->id]);
    foreach ($listings as $listing) {
        Transaction::factory()->offered()->for($listing, 'listing')->create(['buyer_id' => $me->id]);
    }

    actingAs($me);

    DB::flushQueryLog();
    DB::enableQueryLog();
    Livewire::test(TransactionsIndexPage::class)->assertOk();
    $small = count(DB::getQueryLog());

    $more = Listing::factory()->active()->count(10)->create(['seller_id' => $seller->id]);
    foreach ($more as $listing) {
        Transaction::factory()->offered()->for($listing, 'listing')->create(['buyer_id' => $me->id]);
    }

    DB::flushQueryLog();
    Livewire::test(TransactionsIndexPage::class)->assertOk();
    $large = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($large)->toBe($small);
});
