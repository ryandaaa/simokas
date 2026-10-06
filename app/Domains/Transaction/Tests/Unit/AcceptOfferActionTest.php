<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Unit;

use App\Domains\Transaction\Actions\AcceptOffer;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('TRX-T06 allows seller to accept offer and changes status to accepted', function () {
    $transaction = Transaction::factory()->offered()->create();

    $action = app(AcceptOffer::class);
    $result = $action->handle($transaction);

    expect($result->status)->toBe(TransactionStatus::Accepted);
});

it('is idempotent when transaction already accepted', function () {
    $transaction = Transaction::factory()->accepted()->create();

    $action = app(AcceptOffer::class);
    $result = $action->handle($transaction);

    expect($result->status)->toBe(TransactionStatus::Accepted);
});
