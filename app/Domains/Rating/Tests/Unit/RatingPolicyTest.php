<?php

declare(strict_types=1);

namespace App\Domains\Rating\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Rating\Models\Rating;
use App\Domains\Rating\Policies\RatingPolicy;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('RTG-T06 rating cannot be updated or deleted by buyer or any user (RTG-R3)', function () {
    $buyer = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $admin = User::factory()->admin()->create();

    $rating = Rating::factory()->create([
        'buyer_id' => $buyer->id,
        'inspector_id' => $inspector->id,
    ]);

    $policy = new RatingPolicy;

    // Buyer cannot update or delete
    expect($policy->update($buyer, $rating))->toBeFalse()
        ->and($policy->delete($buyer, $rating))->toBeFalse();

    // Inspector cannot update or delete
    expect($policy->update($inspector, $rating))->toBeFalse()
        ->and($policy->delete($inspector, $rating))->toBeFalse();

    // Admin cannot update or delete directly (only hide/unhide)
    expect($policy->update($admin, $rating))->toBeFalse()
        ->and($policy->delete($admin, $rating))->toBeFalse();
});

it('authorizes create only for transaction buyer on completed transaction with inspector', function () {
    $buyer = User::factory()->user()->create();
    $seller = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $otherUser = User::factory()->user()->create();

    $listing = Listing::factory()->create(['seller_id' => $seller->id]);

    $transaction = Transaction::factory()->create([
        'listing_id' => $listing->id,
        'buyer_id' => $buyer->id,
        'inspector_id' => $inspector->id,
        'status' => TransactionStatus::Completed,
    ]);

    $policy = new RatingPolicy;

    expect($policy->create($buyer, $transaction))->toBeTrue()
        ->and($policy->create($otherUser, $transaction))->toBeFalse()
        ->and($policy->create($seller, $transaction))->toBeFalse()
        ->and($policy->create($inspector, $transaction))->toBeFalse();
});

it('denies create if transaction not completed or has no inspector or already rated', function () {
    $buyer = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();

    $policy = new RatingPolicy;

    // Not completed
    $txNotCompleted = Transaction::factory()->create([
        'buyer_id' => $buyer->id,
        'inspector_id' => $inspector->id,
        'status' => TransactionStatus::PaidHeld,
    ]);
    expect($policy->create($buyer, $txNotCompleted))->toBeFalse();

    // No inspector
    $txNoInspector = Transaction::factory()->create([
        'buyer_id' => $buyer->id,
        'inspector_id' => null,
        'status' => TransactionStatus::Completed,
    ]);
    expect($policy->create($buyer, $txNoInspector))->toBeFalse();

    // Already rated
    $txCompleted = Transaction::factory()->create([
        'buyer_id' => $buyer->id,
        'inspector_id' => $inspector->id,
        'status' => TransactionStatus::Completed,
    ]);
    Rating::factory()->create([
        'transaction_id' => $txCompleted->id,
        'buyer_id' => $buyer->id,
        'inspector_id' => $inspector->id,
    ]);
    expect($policy->create($buyer, $txCompleted))->toBeFalse();
});

it('authorizes hide and unhide only for active admin', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();

    $rating = Rating::factory()->create();

    $policy = new RatingPolicy;

    expect($policy->hide($admin, $rating))->toBeTrue()
        ->and($policy->unhide($admin, $rating))->toBeTrue()
        ->and($policy->hide($user, $rating))->toBeFalse()
        ->and($policy->unhide($user, $rating))->toBeFalse()
        ->and($policy->hide($inspector, $rating))->toBeFalse()
        ->and($policy->unhide($inspector, $rating))->toBeFalse();
});
