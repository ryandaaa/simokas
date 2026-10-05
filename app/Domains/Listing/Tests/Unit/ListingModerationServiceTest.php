<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Contracts\ListingModerationContract;
use App\Domains\Listing\DTOs\ListingModerationDetail;
use App\Domains\Listing\DTOs\ListingModerationQueueItem;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns the pending queue oldest first without internal models', function () {
    $older = Listing::factory()->pendingReview()->create(['created_at' => now()->subDays(3)]);
    $newer = Listing::factory()->pendingReview()->create(['created_at' => now()->subDay()]);
    Listing::factory()->active()->create();

    $contract = app(ListingModerationContract::class);
    $items = $contract->queue(perPage: 12);

    $ids = array_map(fn (ListingModerationQueueItem $item): string => $item->id, $items->items());

    expect($ids)->toBe([$older->id, $newer->id]);
    expect($items->items()[0])->toBeInstanceOf(ListingModerationQueueItem::class);
});

it('returns moderation detail with private fields for admin review', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->pendingReview()->withPhotos(3)->create([
        'seller_id' => $seller->id,
        'address_full' => 'Jl. HR. Soebrantas No. 12, Tampan, Pekanbaru',
    ]);

    $contract = app(ListingModerationContract::class);
    $detail = $contract->detail($listing->id);

    expect($detail)->toBeInstanceOf(ListingModerationDetail::class)
        ->and($detail->id)->toBe($listing->id)
        ->and($detail->addressFull)->toBe('Jl. HR. Soebrantas No. 12, Tampan, Pekanbaru')
        ->and($detail->lat)->not->toBeNull()
        ->and($detail->photos)->toHaveCount(3);
});

it('approves and rejects through the contract actions', function () {
    $admin = User::factory()->admin()->create();
    $contract = app(ListingModerationContract::class);

    $toApprove = Listing::factory()->pendingReview()->create();
    $contract->approve($admin, $toApprove->id);
    expect($toApprove->fresh()->status)->toBe(ListingStatus::Active);

    $toReject = Listing::factory()->pendingReview()->create();
    $contract->reject($admin, $toReject->id, 'Foto tidak sesuai ketentuan.');
    $fresh = $toReject->fresh();
    expect($fresh->status)->toBe(ListingStatus::Rejected)
        ->and($fresh->rejection_reason)->toBe('Foto tidak sesuai ketentuan.');
});
