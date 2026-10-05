<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('LST-T12 forbids other users from editing listing (dataset role)', function (string $role) {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->create(['seller_id' => $owner->id]);

    $url = route('listings.edit', $listing);

    if ($role === 'guest') {
        get($url)->assertRedirect(route('login'));

        return;
    }

    if ($role === 'blocked_owner') {
        $owner->update(['status' => UserStatus::Blocked]);
        actingAs($owner);
        get($url)->assertRedirect('/blocked');

        return;
    }

    $actor = match ($role) {
        'other_user' => User::factory()->user()->create(),
        'inspector' => User::factory()->inspector()->create(),
        default => throw new InvalidArgumentException("Unhandled role: {$role}"),
    };

    actingAs($actor);
    get($url)->assertForbidden();

})->with([
    'guest' => ['guest'],
    'other user' => ['other_user'],
    'inspector' => ['inspector'],
    'blocked owner' => ['blocked_owner'],
]);

it('allows owner and admin to access edit listing', function () {
    $owner = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();

    $listing = Listing::factory()->create(['seller_id' => $owner->id]);
    $url = route('listings.edit', $listing);

    // Owner can access
    actingAs($owner);
    get($url)->assertOk();

    // Admin can access
    actingAs($admin);
    get($url)->assertOk();
});
