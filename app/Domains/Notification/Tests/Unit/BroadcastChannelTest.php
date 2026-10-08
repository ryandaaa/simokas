<?php

declare(strict_types=1);

namespace App\Domains\Notification\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Notification\Broadcasting\UserChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('NTF-T06 allows user channel subscription for the owner only', function () {
    $user = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    $blocked = User::factory()->user()->blocked()->create();

    $channel = app(UserChannel::class);

    expect($channel->join($user, (string) $user->getKey()))->toBeTrue()
        ->and($channel->join($other, (string) $user->getKey()))->toBeFalse()
        ->and($channel->join($blocked, (string) $blocked->getKey()))->toBeFalse()
        ->and($channel->join(null, (string) $user->getKey()))->toBeFalse();
});
