<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Livewire\RequestInspectionPage;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('shows Ajukan Inspeksi button on catalog detail for guests linking to login', function () {
    $listing = Listing::factory()->active()->create();

    $response = get(route('catalog.show', ['listing' => $listing->id]));

    $response->assertOk()
        ->assertSee(__('catalog.detail.action_request_inspection'))
        ->assertSee(route('login'));
});

it('shows Ajukan Inspeksi button on catalog detail for authenticated eligible user linking to jobs.request', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    actingAs($buyer);
    $response = get(route('catalog.show', ['listing' => $listing->id]));

    $response->assertOk()
        ->assertSee(__('catalog.detail.action_request_inspection'))
        ->assertSee(route('jobs.request', ['listing' => $listing->id]));
});

it('does not show Ajukan Inspeksi button on catalog detail for inspector, owner, or admin', function (string $actorType) {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $actor = match ($actorType) {
        'inspector' => User::factory()->inspector()->create(),
        'owner' => $seller,
        'admin' => User::factory()->admin()->create(),
    };

    actingAs($actor);
    $response = get(route('catalog.show', ['listing' => $listing->id]));

    $response->assertOk()
        ->assertDontSee(__('catalog.detail.action_request_inspection'));
})->with(['inspector', 'owner', 'admin']);

it('connects the catalog button all the way to payment show page', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    actingAs($buyer);

    // 1. Visit catalog detail
    $catalogResponse = get(route('catalog.show', ['listing' => $listing->id]));
    $catalogResponse->assertOk()
        ->assertSee(route('jobs.request', ['listing' => $listing->id]));

    // 2. Open request page
    $requestResponse = get(route('jobs.request', ['listing' => $listing->id]));
    $requestResponse->assertOk();

    // 3. Submit request in Livewire component
    $livewire = Livewire::test(RequestInspectionPage::class, ['listing' => $listing])
        ->call('submit');

    // 4. Assert redirection leads to payments.show
    $livewire->assertRedirect();
    $redirectUrl = $livewire->effects['redirect'] ?? '';
    expect($redirectUrl)->toContain('/payments/');
});
