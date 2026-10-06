<?php

declare(strict_types=1);

namespace App\Domains\Listing\Tests\Unit;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Events\UserActivated;
use App\Domains\Identity\Events\UserBlocked;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\DeactivateListingsOfUser;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('LST-T15: UserBlocked deactivates active listings', function () {
    it('LST-T15 deactivates only active listings of the blocked user', function () {
        $seller = User::factory()->user()->active()->create();
        $other = User::factory()->user()->active()->create();

        $active1 = Listing::factory()->active()->create(['seller_id' => $seller->id]);
        $active2 = Listing::factory()->active()->create(['seller_id' => $seller->id]);
        $inactive = Listing::factory()->create(['seller_id' => $seller->id, 'status' => ListingStatus::Inactive]);
        $sold = Listing::factory()->create(['seller_id' => $seller->id, 'status' => ListingStatus::Sold]);
        $otherActive = Listing::factory()->active()->create(['seller_id' => $other->id]);

        UserBlocked::dispatch(userId: (string) $seller->id, blockedBy: 'admin-id', reason: 'spam');

        expect($active1->fresh()->status)->toBe(ListingStatus::Inactive);
        expect($active2->fresh()->status)->toBe(ListingStatus::Inactive);
        expect($inactive->fresh()->status)->toBe(ListingStatus::Inactive);
        expect($sold->fresh()->status)->toBe(ListingStatus::Sold);
        expect($otherActive->fresh()->status)->toBe(ListingStatus::Active);
    });

    it('LST-T15 deactivation is idempotent', function () {
        $seller = User::factory()->user()->active()->create();
        $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

        $action = app(DeactivateListingsOfUser::class);

        expect($action->handle((string) $seller->id))->toBe(1);
        expect($action->handle((string) $seller->id))->toBe(0);
        expect($listing->fresh()->status)->toBe(ListingStatus::Inactive);
    });

    it('LST-T15 activating the user does not reactivate listings', function () {
        $seller = User::factory()->user()->blocked()->create();
        $listing = Listing::factory()->create(['seller_id' => $seller->id, 'status' => ListingStatus::Inactive]);

        UserActivated::dispatch(userId: (string) $seller->id, activatedBy: 'admin-id');

        expect($seller->fresh()->status)->toBe(UserStatus::Blocked);
        expect($listing->fresh()->status)->toBe(ListingStatus::Inactive);
    });
});
