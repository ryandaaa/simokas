<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Policies\TransactionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPolicyTest(): TransactionPolicy
{
    return app(TransactionPolicy::class);
}

it('TRX-T06 allows buyer to view their transaction', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->create(['listing_id' => $listing->id, 'buyer_id' => $buyer->id]);

    $policy = createPolicyTest();

    expect($policy->view($buyer, $transaction))->toBeTrue();
});

it('TRX-T06 allows seller to view their transaction', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->create(['listing_id' => $listing->id, 'buyer_id' => $buyer->id]);

    $policy = createPolicyTest();

    expect($policy->view($seller, $transaction))->toBeTrue();
});

it('TRX-T06 forbids other users from viewing transaction', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $otherUser = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->create(['listing_id' => $listing->id, 'buyer_id' => $buyer->id]);

    $policy = createPolicyTest();

    expect($policy->view($otherUser, $transaction))->toBeFalse();
});

it('TRX-T06 allows only seller to accept offer', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $otherUser = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->create(['listing_id' => $listing->id, 'buyer_id' => $buyer->id]);

    $policy = createPolicyTest();

    expect($policy->accept($seller, $transaction))->toBeTrue();
    expect($policy->accept($buyer, $transaction))->toBeFalse();
    expect($policy->accept($otherUser, $transaction))->toBeFalse();
});

it('TRX-T06 allows only seller to reject offer', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $otherUser = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->create(['listing_id' => $listing->id, 'buyer_id' => $buyer->id]);

    $policy = createPolicyTest();

    expect($policy->reject($seller, $transaction))->toBeTrue();
    expect($policy->reject($buyer, $transaction))->toBeFalse();
    expect($policy->reject($otherUser, $transaction))->toBeFalse();
});

it('TRX-T14 forbids blocked users from accepting', function () {
    $seller = User::factory()->user()->blocked()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->create(['listing_id' => $listing->id, 'buyer_id' => $buyer->id]);

    $policy = createPolicyTest();

    expect($policy->accept($seller, $transaction))->toBeFalse();
});

it('TRX-T14 forbids blocked users from rejecting', function () {
    $seller = User::factory()->user()->blocked()->create();
    $buyer = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->create(['listing_id' => $listing->id, 'buyer_id' => $buyer->id]);

    $policy = createPolicyTest();

    expect($policy->reject($seller, $transaction))->toBeFalse();
});
