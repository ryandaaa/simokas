<?php

declare(strict_types=1);

namespace App\Domains\Listing\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Services\DefaultListingActivityChecker;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('TRX-R2 reports an active transaction for running statuses', function (string $status) {
    $listing = Listing::factory()->active()->create();

    Transaction::factory()->create([
        'listing_id' => $listing->id,
        'buyer_id' => User::factory()->user()->create()->id,
        'status' => $status,
    ]);

    expect((new DefaultListingActivityChecker)->hasActiveTransaction($listing->id))->toBeTrue();
})->with(['offered', 'accepted', 'paid_held']);

it('TRX-R2 reports no active transaction for final statuses or no transaction', function (?string $status) {
    $listing = Listing::factory()->active()->create();

    if ($status !== null) {
        Transaction::factory()->create([
            'listing_id' => $listing->id,
            'buyer_id' => User::factory()->user()->create()->id,
            'status' => $status,
        ]);
    }

    expect((new DefaultListingActivityChecker)->hasActiveTransaction($listing->id))->toBeFalse();
})->with([
    'completed' => ['completed'],
    'rejected' => ['rejected'],
    'cancelled' => ['cancelled'],
    'expired' => ['expired'],
    'no transaction' => [null],
]);

it('TRX-R2 ignores transactions of other listings', function () {
    $listing = Listing::factory()->active()->create();
    $other = Listing::factory()->active()->create();

    Transaction::factory()->offered()->create(['listing_id' => $other->id]);

    expect((new DefaultListingActivityChecker)->hasActiveTransaction($listing->id))->toBeFalse()
        ->and((new DefaultListingActivityChecker)->hasActiveTransaction($other->id))->toBeTrue();
});
