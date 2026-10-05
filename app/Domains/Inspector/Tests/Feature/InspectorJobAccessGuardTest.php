<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Define a test route simulating the future /jobs route protected by verified_inspector middleware
    Route::middleware(['web', 'auth', 'not_blocked', 'verified_inspector'])->get('/test-jobs', function () {
        return response()->json(['data' => ['jobs' => []]]);
    });
});

it('ONB-T08 pending and rejected inspectors receive 403 when accessing jobs route (ONB-E4)', function (VerificationStatus $status) {
    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Pending,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'verification_status' => $status,
    ]);

    actingAs($user);
    $response = get('/test-jobs');

    $response->assertForbidden();
})->with([
    'pending inspector' => VerificationStatus::Pending,
    'rejected inspector' => VerificationStatus::Rejected,
]);

it('approved inspector can access jobs route with 200 OK', function () {
    $user = User::factory()->create([
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $user->id,
        'verification_status' => VerificationStatus::Approved,
    ]);

    actingAs($user);
    $response = get('/test-jobs');

    $response->assertOk()
        ->assertJsonPath('data.jobs', []);
});

it('regular user receives 403 when accessing verified inspector route', function () {
    $regularUser = User::factory()->create([
        'role' => UserRole::User,
        'status' => UserStatus::Active,
    ]);

    actingAs($regularUser);
    $response = get('/test-jobs');

    $response->assertForbidden();
});

it('unauthenticated user is redirected to login when accessing verified inspector route', function () {
    $response = get('/test-jobs');

    $response->assertRedirect(route('login'));
});
