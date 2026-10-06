<?php

declare(strict_types=1);

namespace App\Domains\Notification\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Notification\Models\Notification;
use App\Domains\Notification\Policies\NotificationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('NTF-T03 allows the owner to view and update their notification', function (): void {
    $owner = User::factory()->user()->create();
    $notification = Notification::factory()->forUser($owner)->create();
    $policy = new NotificationPolicy;

    expect($policy->view($owner, $notification))->toBeTrue()
        ->and($policy->update($owner, $notification))->toBeTrue();
});

it('NTF-T03 forbids other users from viewing or updating the notification', function (): void {
    $owner = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $notification = Notification::factory()->forUser($owner)->create();
    $policy = new NotificationPolicy;

    expect($policy->view($other, $notification))->toBeFalse()
        ->and($policy->update($other, $notification))->toBeFalse()
        ->and($policy->view($admin, $notification))->toBeFalse();
});

it('NTF-T03 forbids blocked owners from acting on their notification', function (): void {
    $blocked = User::factory()->user()->blocked()->create();
    $notification = Notification::factory()->forUser($blocked)->create();
    $policy = new NotificationPolicy;

    expect($policy->view($blocked, $notification))->toBeFalse()
        ->and($policy->update($blocked, $notification))->toBeFalse();
});
