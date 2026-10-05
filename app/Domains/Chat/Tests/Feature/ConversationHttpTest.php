<?php

declare(strict_types=1);

use App\Domains\Chat\Enums\ConversationContext;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('requires authentication to access chat routes', function () {
    $conversation = Conversation::factory()->create();

    get(route('conversations.index'))->assertRedirect(route('login'));
    get(route('conversations.show', $conversation))->assertRedirect(route('login'));
    post(route('conversations.start'), [
        'context_type' => 'listing',
        'context_id' => (string) Str::uuid(),
    ])->assertRedirect(route('login'));
});

it('rejects blocked user from accessing conversations', function () {
    $blocked = User::factory()->create(['status' => UserStatus::Blocked]);
    $conversation = Conversation::factory()->create();

    actingAs($blocked)->get(route('conversations.index'))->assertRedirect('/blocked');
    actingAs($blocked)->get(route('conversations.show', $conversation))->assertRedirect('/blocked');
});

it('denies outsider from viewing conversation (CHT-R3, CHT-E3)', function () {
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $outsider = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    actingAs($outsider)
        ->get(route('conversations.show', $conversation))
        ->assertForbidden();
});

it('allows participant to view conversation (CHT-R3)', function () {
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    actingAs($buyer)
        ->get(route('conversations.show', $conversation))
        ->assertOk()
        ->assertSee($seller->name);

    actingAs($seller)
        ->get(route('conversations.show', $conversation))
        ->assertOk()
        ->assertSee($buyer->name);
});

it('starts conversation via HTTP and redirects to conversation detail', function () {
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $response = actingAs($buyer)->post(route('conversations.start'), [
        'context_type' => 'listing',
        'context_id' => $listing->id,
    ]);

    $conversation = Conversation::query()
        ->where('context_type', ConversationContext::Listing)
        ->where('context_id', $listing->id)
        ->first();

    expect($conversation)->not->toBeNull();
    $response->assertRedirect(route('conversations.show', $conversation));
});

it('ensures conversation responses never leak private listing data', function () {
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $listing = Listing::factory()->create([
        'seller_id' => $seller->id,
        'lat' => '-6.1234567',
        'lng' => '106.1234567',
        'address_full' => 'Jalan Rahasia Nomor 99 RT 01 RW 02 Pekanbaru',
        'chassis_number' => 'MH1JFD2199K123456',
    ]);

    $conversation = Conversation::factory()
        ->forListing($listing)
        ->withMembers($buyer, $seller)
        ->create();

    $response = actingAs($buyer)->get(route('conversations.show', $conversation));
    $response->assertOk();

    $content = $response->getContent();
    expect($content)->not->toContain('Jalan Rahasia Nomor 99 RT 01 RW 02 Pekanbaru')
        ->and($content)->not->toContain('MH1JFD2199K123456')
        ->and($content)->not->toContain('-6.1234567')
        ->and($content)->not->toContain('106.1234567');
});

it('CHT-R2 authorizes or rejects roles on HTTP POST /conversations/start for job and listing contexts', function (
    string $contextType,
    string $roleScenario,
    int $expectedStatus,
) {
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $requester = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $assignedInspector = User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]);
    $otherInspector = User::factory()->create(['role' => UserRole::Inspector, 'status' => UserStatus::Active]);
    $outsiderBuyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => UserStatus::Active]);
    $blockedUser = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Blocked]);

    $listing = Listing::factory()->create([
        'seller_id' => $seller->id,
        'status' => 'active',
    ]);
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'requester_id' => $requester->id,
        'inspector_id' => $assignedInspector->id,
    ]);

    $actor = match ($roleScenario) {
        'pemohon' => $requester,
        'inspektor pengambil' => $assignedInspector,
        'inspektor lain' => $otherInspector,
        'pembeli luar' => $outsiderBuyer,
        'admin' => $admin,
        'pengguna terblokir' => $blockedUser,
        'anonim' => null,
        default => throw new InvalidArgumentException("Unknown scenario: {$roleScenario}"),
    };

    $contextId = $contextType === 'job' ? $job->id : $listing->id;

    $req = $actor !== null ? actingAs($actor) : test();

    $response = $req->post(route('conversations.start'), [
        'context_type' => $contextType,
        'context_id' => $contextId,
    ]);

    if ($expectedStatus === 302) {
        $response->assertStatus(302);
    } else {
        $response->assertStatus($expectedStatus);
    }
})->with([
    // Job context dataset
    'job: pemohon' => ['job', 'pemohon', 302],
    'job: inspektor pengambil' => ['job', 'inspektor pengambil', 302],
    'job: inspektor lain' => ['job', 'inspektor lain', 403],
    'job: pembeli luar' => ['job', 'pembeli luar', 403],
    'job: admin' => ['job', 'admin', 403],
    'job: anonim' => ['job', 'anonim', 302], // redirects to login
    'job: pengguna terblokir' => ['job', 'pengguna terblokir', 302], // redirects to /blocked

    // Listing context dataset (public listing)
    'listing: pemohon (buyer)' => ['listing', 'pemohon', 302],
    'listing: inspektor pengambil' => ['listing', 'inspektor pengambil', 302],
    'listing: inspektor lain' => ['listing', 'inspektor lain', 302],
    'listing: pembeli luar' => ['listing', 'pembeli luar', 302],
    'listing: anonim' => ['listing', 'anonim', 302], // redirects to login
    'listing: pengguna terblokir' => ['listing', 'pengguna terblokir', 302], // redirects to /blocked
]);
