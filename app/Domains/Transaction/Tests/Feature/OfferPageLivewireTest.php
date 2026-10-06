<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Livewire\OfferPage;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('TRX-T01 redirects guest to login when accessing the offer page', function () {
    $listing = Listing::factory()->active()->create();

    get(route('transactions.offer', ['listing' => $listing->id]))
        ->assertRedirect(route('login'));
});

it('TRX-T14 forbids inspectors and admins from accessing transactions.offer', function (string $role) {
    $user = match ($role) {
        'inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
        default => throw new InvalidArgumentException("Unknown role: {$role}"),
    };
    $listing = Listing::factory()->active()->create();

    actingAs($user);
    get(route('transactions.offer', ['listing' => $listing->id]))
        ->assertForbidden();
})->with(['inspector', 'admin']);

it('TRX-T01 renders the offer form with listing price as default', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'title' => 'Toyota Avanza 2020',
        'price' => 150000000,
    ]);

    actingAs($buyer);

    Livewire::test(OfferPage::class, ['listing' => $listing])
        ->assertOk()
        ->assertSee('Toyota Avanza 2020')
        ->assertSee('Rp150.000.000')
        ->assertSee(__('transaction.offer.submit'))
        ->assertSet('priceAmount', '150000000');
});

it('TRX-T01 submits an offer and redirects to the transaction detail', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'price' => 150000000,
    ]);

    actingAs($buyer);

    $component = Livewire::test(OfferPage::class, ['listing' => $listing])
        ->set('priceAmount', '140000000')
        ->set('notes', 'Bisa survei hari Sabtu?')
        ->call('submit');

    $transaction = Transaction::query()->firstOrFail();

    expect($transaction->price_amount)->toBe(140000000)
        ->and($transaction->notes)->toBe('Bisa survei hari Sabtu?');

    $component->assertRedirect(route('transactions.show', ['transaction' => $transaction->id]));
});

it('validates price and notes on the offer form', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    actingAs($buyer);

    Livewire::test(OfferPage::class, ['listing' => $listing])
        ->set('priceAmount', '0')
        ->set('notes', str_repeat('a', 501))
        ->call('submit')
        ->assertHasErrors(['priceAmount' => 'min', 'notes' => 'max']);

    expect(Transaction::query()->count())->toBe(0);
});

it('TRX-T02 shows a clear error when offering on own listing', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    actingAs($seller);

    Livewire::test(OfferPage::class, ['listing' => $listing])
        ->call('submit')
        ->assertSee(__('transaction.errors.cannot_buy_own_listing'));

    expect(Transaction::query()->count())->toBe(0);
});

it('returns 404 on the offer page for non-offerable listing statuses', function (string $state) {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->$state()->create(['seller_id' => $seller->id]);

    actingAs($buyer);

    get(route('transactions.offer', ['listing' => $listing->id]))
        ->assertNotFound();
})->with(['pendingReview', 'rejected', 'inactive', 'sold', 'inTransaction']);

it('renders the offer page for offerable listing statuses', function (string $state) {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->$state()->create(['seller_id' => $seller->id]);

    actingAs($buyer);

    get(route('transactions.offer', ['listing' => $listing->id]))
        ->assertOk();
})->with(['active', 'inInspection']);
