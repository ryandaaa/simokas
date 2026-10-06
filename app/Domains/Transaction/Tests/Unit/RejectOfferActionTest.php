<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Actions\RejectOffer;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('TRX-T06 allows seller to reject offer and changes status to rejected', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->offered()->for($listing, 'listing')->create();

    $action = app(RejectOffer::class);
    $result = $action->handle($seller, $transaction);

    expect($result->status)->toBe(TransactionStatus::Rejected);
});

it('is idempotent when transaction already rejected', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->rejected()->for($listing, 'listing')->create();

    $action = app(RejectOffer::class);
    $result = $action->handle($seller, $transaction);

    expect($result->status)->toBe(TransactionStatus::Rejected);
});
