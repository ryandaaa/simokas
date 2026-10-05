<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Contracts\ListingActivityChecker;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Livewire\EditListing;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Tests\Fakes\FakeListingActivityChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
});

it('LST-T08 shows locked core fields with a reason when a job is active', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->withPhotos(3)->withInspectionSlots()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    $checker = new FakeListingActivityChecker;
    $checker->withActiveJob($listing->id);
    app()->instance(ListingActivityChecker::class, $checker);

    Livewire::test(EditListing::class, ['listing' => $listing])
        ->assertSee(__('listing.edit.core_locked_reason'))
        ->assertSee(__('listing.edit.core_badge'));
});

it('LST-T09 deactivates from the edit page, and refuses while a job is active', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->withPhotos(3)->withInspectionSlots()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    $checker = new FakeListingActivityChecker;
    app()->instance(ListingActivityChecker::class, $checker);

    $page = Livewire::test(EditListing::class, ['listing' => $listing]);

    $checker->withActiveJob($listing->id);

    $page->call('confirmDeactivate')
        ->call('deactivate')
        ->assertSee(__('listing.errors.deactivation_blocked'));

    expect($listing->fresh()->status)->toBe(ListingStatus::Active);

    $freshChecker = new FakeListingActivityChecker;
    app()->instance(ListingActivityChecker::class, $freshChecker);

    Livewire::test(EditListing::class, ['listing' => $listing->fresh()])
        ->call('confirmDeactivate')
        ->call('deactivate')
        ->assertRedirect(route('listings.mine'));

    expect($listing->fresh()->status)->toBe(ListingStatus::Inactive);
});

it('reactivates an inactive listing from the edit page', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->inactive()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    Livewire::test(EditListing::class, ['listing' => $listing])
        ->assertSee(__('listing.edit.reactivate'))
        ->call('confirmReactivate')
        ->call('reactivate')
        ->assertRedirect(route('listings.mine'));

    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('LST-T12 forbids every role except owner and admin from saving or toggling status', function (string $role) {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->withPhotos(3)->withInspectionSlots()->create(['seller_id' => $owner->id]);

    if ($role === 'guest') {
        // Livewire mount authorization denies guests with 403; the HTTP
        // redirect to login is covered by the route-level T12 test.
        Livewire::test(EditListing::class, ['listing' => $listing])->assertForbidden();

        return;
    }

    if ($role === 'blocked_owner') {
        $owner->update(['status' => UserStatus::Blocked]);
        actingAs($owner->fresh());
        Livewire::test(EditListing::class, ['listing' => $listing])->assertForbidden();

        return;
    }

    $actor = match ($role) {
        'other_user' => User::factory()->user()->create(),
        'inspector' => User::factory()->inspector()->create(),
        default => throw new InvalidArgumentException("Unhandled role: {$role}"),
    };

    actingAs($actor);
    Livewire::test(EditListing::class, ['listing' => $listing])->assertForbidden();
})->with([
    'guest' => ['guest'],
    'other user' => ['other_user'],
    'inspector' => ['inspector'],
    'blocked owner' => ['blocked_owner'],
]);

it('lets owner and admin save non-core edits from the edit page', function () {
    $owner = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->active()->withPhotos(3)->withInspectionSlots()->create(['seller_id' => $owner->id]);

    actingAs($owner);
    Livewire::test(EditListing::class, ['listing' => $listing])
        ->set('price', 155_000_000)
        ->call('save')
        ->assertSee(__('listing.edit.saved_direct'));

    expect($listing->fresh()->price)->toBe(155_000_000);

    actingAs($admin);
    Livewire::test(EditListing::class, ['listing' => $listing->fresh()])
        ->set('price', 156_000_000)
        ->call('save')
        ->assertSee(__('listing.edit.saved_direct'));

    expect($listing->fresh()->price)->toBe(156_000_000);
});

it('asks for confirmation before applying a core change from the edit page', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->withPhotos(3)->withInspectionSlots()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    $page = Livewire::test(EditListing::class, ['listing' => $listing])
        ->set('odometer_km', 999999)
        ->call('save')
        ->assertSee(__('listing.edit.confirm_core_title'));

    expect($listing->fresh()->status)->toBe(ListingStatus::Active);

    $page->call('confirmSave')->assertSee(__('listing.edit.sent_to_review'));

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::PendingReview)
        ->and($fresh->odometer_km)->toBe(999999);
});

it('shows the price offer warning while editing (LST-E6)', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->withPhotos(3)->withInspectionSlots()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    Livewire::test(EditListing::class, ['listing' => $listing])
        ->assertSee(__('listing.edit.price_offer_warning'));
});

it('leaves no orphan files when saving with invalid photos', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->withPhotos(3)->withInspectionSlots()->create(['seller_id' => $owner->id]);
    actingAs($owner);

    $page = Livewire::test(EditListing::class, ['listing' => $listing])
        ->set('newPhotos', [
            UploadedFile::fake()->image('a.jpg')->size(50),
            UploadedFile::fake()->image('b.jpg')->size(50),
            UploadedFile::fake()->image('c.jpg')->size(50),
            UploadedFile::fake()->image('d.jpg')->size(50),
            UploadedFile::fake()->image('e.jpg')->size(50),
            UploadedFile::fake()->image('f.jpg')->size(50),
            UploadedFile::fake()->image('g.jpg')->size(50),
            UploadedFile::fake()->image('h.jpg')->size(50),
        ])
        ->call('save');

    $page->assertSee(__('listing.validation.photos_count'));

    expect($listing->fresh()->photos)->toHaveCount(3);
    expect(Storage::disk('private')->allFiles('listings/'.$listing->id))->toBe([]);
});
