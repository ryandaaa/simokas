<?php

declare(strict_types=1);

use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Policies\ConversationPolicy;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('CHT-T03 authorizes participants to view conversation and private channel', function () {
    $policy = new ConversationPolicy();
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    expect($policy->view($buyer, $conversation))->toBeTrue()
        ->and($policy->sendMessage($buyer, $conversation))->toBeTrue()
        ->and($policy->viewChannel($buyer, $conversation->id))->toBeTrue()
        ->and(Gate::forUser($buyer)->allows('view', $conversation))->toBeTrue()
        ->and(Gate::forUser($buyer)->allows('sendMessage', $conversation))->toBeTrue()
        ->and($policy->view($seller, $conversation))->toBeTrue()
        ->and($policy->sendMessage($seller, $conversation))->toBeTrue()
        ->and($policy->viewChannel($seller, $conversation->id))->toBeTrue()
        ->and(Gate::forUser($seller)->allows('view', $conversation))->toBeTrue()
        ->and(Gate::forUser($seller)->allows('sendMessage', $conversation))->toBeTrue();

    // Non-existent channel check
    expect($policy->viewChannel($buyer, '00000000-0000-0000-0000-000000000000'))->toBeFalse();
});

it('CHT-T03 denies outsiders of any role from viewing or sending', function (string $roleScenario) {
    $policy = new ConversationPolicy();
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    $outsider = match ($roleScenario) {
        'outsider buyer' => User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]),
        'outsider inspector' => User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]),
        'admin outsider' => User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Active]),
        'blocked buyer' => User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Blocked]),
    };

    expect($policy->view($outsider, $conversation))->toBeFalse()
        ->and($policy->sendMessage($outsider, $conversation))->toBeFalse()
        ->and($policy->viewChannel($outsider, $conversation->id))->toBeFalse()
        ->and(Gate::forUser($outsider)->allows('view', $conversation))->toBeFalse()
        ->and(Gate::forUser($outsider)->allows('sendMessage', $conversation))->toBeFalse();
})->with([
    'outsider buyer' => ['outsider buyer'],
    'outsider inspector' => ['outsider inspector'],
    'admin outsider' => ['admin outsider'],
    'blocked buyer' => ['blocked buyer'],
]);

it('CHT-T03 denies blocked participants', function () {
    $policy = new ConversationPolicy();
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Blocked]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    expect($policy->view($buyer, $conversation))->toBeFalse()
        ->and($policy->sendMessage($buyer, $conversation))->toBeFalse()
        ->and($policy->viewChannel($buyer, $conversation->id))->toBeFalse();
});
