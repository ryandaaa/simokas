<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('TRX-T01 shows Ajukan Pembelian on catalog detail for eligible user on active listing', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    actingAs($buyer);
    $response = get(route('catalog.show', ['listing' => $listing->id]));

    $response->assertOk()
        ->assertSee(__('catalog.detail.action_request_purchase'))
        ->assertSee(route('transactions.offer', ['listing' => $listing->id]));
});

it('TRX-T01 shows Ajukan Pembelian on in_inspection listings', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->create(['seller_id' => $seller->id]);

    actingAs($buyer);
    $response = get(route('catalog.show', ['listing' => $listing->id]));

    $response->assertOk()
        ->assertSee(__('catalog.detail.action_request_purchase'));
});

it('does not show Ajukan Pembelian to guest, owner, inspector, or admin', function (string $actorType) {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $actor = match ($actorType) {
        'owner' => $seller,
        'inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
        default => throw new InvalidArgumentException("Unknown actor type: {$actorType}"),
    };

    actingAs($actor);
    $response = get(route('catalog.show', ['listing' => $listing->id]));

    $response->assertOk()
        ->assertDontSee(__('catalog.detail.action_request_purchase'));
})->with(['owner', 'inspector', 'admin']);

it('does not show Ajukan Pembelian to guests', function () {
    $listing = Listing::factory()->active()->create();

    get(route('catalog.show', ['listing' => $listing->id]))
        ->assertOk()
        ->assertDontSee(__('catalog.detail.action_request_purchase'));
});

it('does not show Ajukan Pembelian on sold listings', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->sold()->create(['seller_id' => $seller->id]);

    actingAs($buyer);
    $response = get(route('catalog.show', ['listing' => $listing->id]));

    $response->assertOk()
        ->assertDontSee(__('catalog.detail.action_request_purchase'));
});
