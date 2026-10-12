<?php

declare(strict_types=1);

namespace Tests\Feature\Acceptance;

use App\Domains\Chat\Actions\SendMessage;
use App\Domains\Chat\Livewire\ConversationList;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('JOB-T02 (PRD-28.3-Item-13) displays Ajukan Inspeksi on active listing without active job and conceals it on non-active listings', function () {
    $seller = User::factory()->user()->create([
        'name' => 'Penjual Uji Penerimaan',
        'email' => 'penjual-terima-13@example.com',
        'phone' => '081234560001',
    ]);

    $buyer = User::factory()->user()->create([
        'name' => 'Pembeli Uji Penerimaan',
        'email' => 'pembeli-terima-13@example.com',
        'phone' => '081234560002',
    ]);

    // 1. Active listing without active job: button is visible to eligible buyer
    $activeListing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'title' => 'Toyota Avanza 2020 Aktif',
    ]);

    actingAs($buyer);
    get(route('catalog.show', ['listing' => $activeListing->id]))
        ->assertOk()
        ->assertSee(__('catalog.detail.action_request_inspection'))
        ->assertSee(route('jobs.request', ['listing' => $activeListing->id]));

    // 2. Listing in_inspection (has active paid job): button is NOT visible
    $inInspectionListing = Listing::factory()->inInspection()->create([
        'seller_id' => $seller->id,
        'title' => 'Honda Brio 2021 Dalam Inspeksi',
    ]);

    get(route('catalog.show', ['listing' => $inInspectionListing->id]))
        ->assertOk()
        ->assertDontSee(__('catalog.detail.action_request_inspection'));

    // 3. Listing in_transaction: button is NOT visible
    $inTransactionListing = Listing::factory()->inTransaction()->create([
        'seller_id' => $seller->id,
        'title' => 'Suzuki Ertiga 2019 Dalam Transaksi',
    ]);

    get(route('catalog.show', ['listing' => $inTransactionListing->id]))
        ->assertOk()
        ->assertDontSee(__('catalog.detail.action_request_inspection'));

    // 4. Listing sold: button is NOT visible
    $soldListing = Listing::factory()->sold()->create([
        'seller_id' => $seller->id,
        'title' => 'Daihatsu Xenia 2018 Terjual',
    ]);

    get(route('catalog.show', ['listing' => $soldListing->id]))
        ->assertOk()
        ->assertDontSee(__('catalog.detail.action_request_inspection'));
});

it('CHT-T06 (PRD-28.9-Item-46) displays unread badge on conversation list when participant receives new messages', function () {
    $buyer = User::factory()->user()->create([
        'name' => 'Pembeli Chat Penerimaan',
        'email' => 'pembeli-chat-46@example.com',
        'phone' => '081234560003',
    ]);

    $seller = User::factory()->user()->create([
        'name' => 'Penjual Chat Penerimaan',
        'email' => 'penjual-chat-46@example.com',
        'phone' => '081234560004',
    ]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    // Send two messages from buyer to seller
    /** @var SendMessage $sendMessage */
    $sendMessage = app(SendMessage::class);
    $sendMessage->handle($buyer, $conversation, 'Pesan pertama dari pembeli');
    $sendMessage->handle($buyer, $conversation, 'Pesan kedua dari pembeli');

    expect($conversation->unreadCountFor($seller))->toBe(2);

    // Seller views conversation list: unread badge with count '2' is rendered
    actingAs($seller);
    Livewire::test(ConversationList::class)
        ->assertOk()
        ->assertSee('Pembeli Chat Penerimaan')
        ->assertSee('Pesan kedua dari pembeli')
        ->assertSee('2');
});
