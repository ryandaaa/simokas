<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Policies\LedgerEntryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function approvedInspector(): User
{
    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    InspectorProfile::factory()->approved()->create(['user_id' => $user->id]);

    return $user;
}

it('allows an approved inspector to view own earnings', function () {
    $inspector = approvedInspector();

    expect(app(LedgerEntryPolicy::class)->viewEarnings($inspector))->toBeTrue()
        ->and(Gate::forUser($inspector)->allows('viewEarnings', LedgerEntry::class))->toBeTrue();
});

it('denies earnings view for everyone else', function (string $scenario) {
    $user = match ($scenario) {
        'pending inspector' => tap(User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Pending]), function (User $u) {
            InspectorProfile::factory()->pending()->create(['user_id' => $u->id]);
        }),
        'rejected inspector' => tap(User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]), function (User $u) {
            InspectorProfile::factory()->rejected()->create(['user_id' => $u->id]);
        }),
        'inspector without profile' => User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]),
        'regular user' => User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]),
        'admin' => User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Active]),
        default => User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Blocked]),
    };

    expect(app(LedgerEntryPolicy::class)->viewEarnings($user))->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewEarnings', LedgerEntry::class))->toBeFalse();
})->with([
    'pending inspector' => ['pending inspector'],
    'rejected inspector' => ['rejected inspector'],
    'inspector without profile' => ['inspector without profile'],
    'regular user' => ['regular user'],
    'admin' => ['admin'],
    'blocked inspector' => ['blocked inspector'],
]);
