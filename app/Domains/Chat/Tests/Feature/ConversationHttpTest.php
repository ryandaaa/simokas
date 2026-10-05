<?php

declare(strict_types=1);

use App\Domains\Chat\Enums\ConversationContext;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
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

    $listing = Listing::factory()->create(['seller_id' => $seller->id]);

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
