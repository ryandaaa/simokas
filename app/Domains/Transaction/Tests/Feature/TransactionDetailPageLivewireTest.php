<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionAccepted;
use App\Domains\Transaction\Events\TransactionRejected;
use App\Domains\Transaction\Livewire\TransactionDetailPage;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('redirects guest to login when accessing transaction detail', function () {
    $transaction = Transaction::factory()->offered()->create();

    get(route('transactions.show', ['transaction' => $transaction->id]))
        ->assertRedirect(route('login'));
});

it('TRX-T14 forbids outsiders from viewing someone else\u2019s transaction', function () {
    $transaction = Transaction::factory()->offered()->create();
    $outsider = User::factory()->user()->create();

    actingAs($outsider);
    get(route('transactions.show', ['transaction' => $transaction->id]))
        ->assertForbidden();
});

it('TRX-T04 shows the seller breakdown with commission', function () {
    $seller = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'title' => 'Honda Brio 2021',
        'price' => 100000000,
    ]);
    $transaction = Transaction::factory()->offered()
        ->for($listing, 'listing')
        ->withInspectionFee(3, $inspector->id)
        ->create(['price_amount' => 100000000]);

    actingAs($seller);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->assertOk()
        ->assertSee('Honda Brio 2021')
        ->assertSee(__('transaction.detail.breakdown_title'))
        ->assertSee('Rp100.000.000')
        ->assertSee('Rp3.000.000')
        ->assertSee('Rp97.000.000')
        ->assertSee(__('transaction.detail.commission_label', ['pct' => 3]));
});

it('TRX-T04 shows full price to seller when there is no inspection', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'price' => 100000000,
    ]);
    $transaction = Transaction::factory()->offered()
        ->for($listing, 'listing')
        ->create(['price_amount' => 100000000, 'commission_pct' => null, 'inspector_id' => null]);

    actingAs($seller);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->assertOk()
        ->assertSee(__('transaction.detail.no_commission'))
        ->assertSee('Rp100.000.000')
        ->assertDontSee('Rp3.000.000');
});

it('shows the accepted notice to the buyer without a pay button yet', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->accepted()
        ->for($listing, 'listing')
        ->create(['buyer_id' => $buyer->id]);

    actingAs($buyer);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->assertOk()
        ->assertSee(__('transaction.detail.buyer_accepted_notice'));
});

it('TRX-T06 lets the seller accept from the detail page', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->offered()
        ->for($listing, 'listing')
        ->create(['buyer_id' => $buyer->id]);

    Event::fake([TransactionAccepted::class]);

    actingAs($seller);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->call('accept')
        ->assertSee(__('transaction.detail.accept_success'));

    expect($transaction->refresh()->status)->toBe(TransactionStatus::Accepted);
    Event::assertDispatchedTimes(TransactionAccepted::class, 1);
});

it('TRX-T06 lets the seller reject from the detail page', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->offered()
        ->for($listing, 'listing')
        ->create(['buyer_id' => $buyer->id]);

    Event::fake([TransactionRejected::class]);

    actingAs($seller);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->call('reject')
        ->assertSee(__('transaction.detail.reject_success'));

    expect($transaction->refresh()->status)->toBe(TransactionStatus::Rejected);
    Event::assertDispatchedTimes(TransactionRejected::class, 1);
});

it('TRX-T06 forbids the buyer from accepting via the detail page', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->offered()
        ->for($listing, 'listing')
        ->create(['buyer_id' => $buyer->id]);

    actingAs($buyer);

    Livewire::test(TransactionDetailPage::class, ['transaction' => $transaction])
        ->call('accept')
        ->assertForbidden();

    expect($transaction->refresh()->status)->toBe(TransactionStatus::Offered);
});
