<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Actions\AcceptOffer;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('TRX-T06 allows seller to accept offer and changes status to accepted', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->offered()->for($listing, 'listing')->create();

    $action = app(AcceptOffer::class);
    $result = $action->handle($seller, $transaction);

    expect($result->status)->toBe(TransactionStatus::Accepted);
});

it('is idempotent when transaction already accepted', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);
    $transaction = Transaction::factory()->accepted()->for($listing, 'listing')->create();

    $action = app(AcceptOffer::class);
    $result = $action->handle($seller, $transaction);

    expect($result->status)->toBe(TransactionStatus::Accepted);
});
