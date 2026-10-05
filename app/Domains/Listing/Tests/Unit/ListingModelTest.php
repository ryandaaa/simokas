<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Models\ListingInspectionSlot;
use App\Domains\Listing\Models\ListingPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('tests listing model relationships and scopes', function () {
    $seller = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();

    $listing = Listing::factory()
        ->pendingReview()
        ->withPhotos(3)
        ->withInspectionSlots(2)
        ->create([
            'seller_id' => $seller->id,
            'validated_by' => $admin->id,
        ]);

    expect($listing->seller->id)->toBe($seller->id)
        ->and($listing->validator->id)->toBe($admin->id)
        ->and($listing->photos)->toHaveCount(3)
        ->and($listing->coverPhoto)->not->toBeNull()
        ->and($listing->inspectionSlots)->toHaveCount(2);

    expect(Listing::query()->pendingReview()->count())->toBe(1)
        ->and(Listing::query()->active()->count())->toBe(0)
        ->and(Listing::query()->publiclyVisible()->count())->toBe(0);

    $listing->update(['status' => ListingStatus::Active]);

    expect(Listing::query()->active()->count())->toBe(1)
        ->and(Listing::query()->publiclyVisible()->count())->toBe(1);
});

it('tests listing photo and slot models and factories', function () {
    $photo = ListingPhoto::factory()->cover()->create();
    $slot = ListingInspectionSlot::factory()->create();

    expect($photo->is_cover)->toBeTrue()
        ->and($photo->listing)->toBeInstanceOf(Listing::class)
        ->and($slot->slot_start)->not->toBeNull()
        ->and($slot->listing)->toBeInstanceOf(Listing::class);
});
