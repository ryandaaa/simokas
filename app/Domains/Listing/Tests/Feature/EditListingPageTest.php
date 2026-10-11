<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Livewire\EditListing;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
});

it('renders the full edit form with core markers and a revalidation warning', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->withPhotos(3)->withInspectionSlots()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    Livewire::test(EditListing::class, ['listing' => $listing])
        ->assertSee(__('listing.edit.title'))
        ->assertSee(__('listing.edit.core_badge'))
        ->assertSee(__('listing.edit.core_warning'))
        ->assertSee(__('listing.fields.vehicle_type'))
        ->assertSee(__('listing.fields.price'))
        ->assertSee(__('listing.edit.section_photos'))
        ->assertSee(__('listing.edit.deactivate'))
        ->assertSee('wire:loading.attr="disabled"', false);
});

it('LST-T20 renders new edit photo previews without signed Livewire preview URLs', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->withPhotos(3)->withInspectionSlots()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    $component = Livewire::test(EditListing::class, ['listing' => $listing])
        ->set('newPhotos', [UploadedFile::fake()->image('new-preview.jpg')->size(50)]);

    expect($component->html())->toContain('x-bind:src="newPhotoPreviewUrls[0]"');
    expect(str_contains($component->html(), '/preview-file/'))->toBeFalse();
});

it('LST-T24 keeps Vite assets outside the Livewire edit component root', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->withPhotos(3)->withInspectionSlots()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    $response = get(route('listings.edit', $listing))->assertOk();
    $html = (string) $response->getContent();

    preg_match('/<([a-z][a-z0-9:-]*)\\b[^>]*\\bwire:id="[^"]+"[^>]*>/i', $html, $matches);

    expect($matches[1] ?? null)->toBe('div')
        ->and($html)->toContain('assets/listing-map');
});

it('disables locked fields and blocks saving while in_transaction', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->inTransaction()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    Livewire::test(EditListing::class, ['listing' => $listing])
        ->assertSee(__('listing.edit.all_locked_notice'))
        ->call('save')
        ->assertSee(__('listing.errors.in_transaction_locked'));

    expect($listing->fresh()->status)->toBe(ListingStatus::InTransaction);
});

it('shows a sold notice without any action buttons', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->sold()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    $page = Livewire::test(EditListing::class, ['listing' => $listing])
        ->assertSee(__('listing.status.sold'));

    $html = $page->html();
    expect($html)->not->toContain(__('listing.edit.save'))
        ->and($html)->not->toContain(__('listing.edit.deactivate'));
});

it('rejects a map pin outside Pekanbaru on save', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->withPhotos(3)->withInspectionSlots()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    Livewire::test(EditListing::class, ['listing' => $listing])
        ->set('lat', 3.0)
        ->set('lng', 101.4)
        ->call('save')
        ->assertHasErrors(['lat']);

    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('prevents double submission with loading states on every action button', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->withPhotos(3)->withInspectionSlots()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    $html = Livewire::test(EditListing::class, ['listing' => $listing])->html();

    expect(substr_count($html, 'wire:loading.attr="disabled"'))->toBeGreaterThanOrEqual(3)
        ->and($html)->toContain('wire:target="save"');

    Livewire::test(EditListing::class, ['listing' => $listing])
        ->call('confirmDeactivate')
        ->assertSee('wire:target="deactivate"', false);

    Livewire::test(EditListing::class, ['listing' => $listing])
        ->set('odometer_km', 999999)
        ->call('save')
        ->assertSee('wire:target="confirmSave"', false);
});
