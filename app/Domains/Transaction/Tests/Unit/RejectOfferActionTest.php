<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Tests\Unit;

use App\Domains\Transaction\Actions\RejectOffer;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('TRX-T06 allows seller to reject offer and changes status to rejected', function () {
    $transaction = Transaction::factory()->offered()->create();

    $action = app(RejectOffer::class);
    $result = $action->handle($transaction);

    expect($result->status)->toBe(TransactionStatus::Rejected);
});

it('is idempotent when transaction already rejected', function () {
    $transaction = Transaction::factory()->rejected()->create();

    $action = app(RejectOffer::class);
    $result = $action->handle($transaction);

    expect($result->status)->toBe(TransactionStatus::Rejected);
});
