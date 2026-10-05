<?php

declare(strict_types=1);

use App\Domains\Chat\Actions\SendMessage;
use App\Domains\Chat\Livewire\ConversationDetail;
use App\Domains\Chat\Livewire\ConversationList;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('CHT-T05 escapes HTML and scripts in messages and displays them as text (CHT-E5)', function () {
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    $hostileBody = 'Halo <script>alert("xss-attack")</script> & <b onclick="alert(1)">tebal</b>';
    app(SendMessage::class)->handle($buyer, $conversation, $hostileBody);

    $component = Livewire::actingAs($seller)
        ->test(ConversationDetail::class, ['conversation' => $conversation]);

    $html = $component->html();

    // Raw executable script tags must NOT appear unescaped in rendered HTML
    expect($html)->not->toContain('<script>alert("xss-attack")</script>')
        ->and($html)->not->toContain('<b onclick="alert(1)">')
        ->and($html)->toContain(e($hostileBody));
});

it('CHT-T11 retrieves new messages via polling without Pusher (CHT-E7)', function () {
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    // Seller mounts the component
    $component = Livewire::actingAs($seller)
        ->test(ConversationDetail::class, ['conversation' => $conversation])
        ->assertDontSee('Pesan baru lewat polling');

    // Buyer sends a new message in database
    app(SendMessage::class)->handle($buyer, $conversation, 'Pesan baru lewat polling');

    // Trigger polling call
    $component->call('pollNewMessages')
        ->assertSee('Pesan baru lewat polling');

    // Verified that unread count was reset to zero upon polling
    expect($conversation->unreadCountFor($seller))->toBe(0);
});

it('sends message via Livewire and clears input upon success', function () {
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    Livewire::actingAs($buyer)
        ->test(ConversationDetail::class, ['conversation' => $conversation])
        ->set('newMessage', 'Selamat siang, masih ada?')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('newMessage', '')
        ->assertSee('Selamat siang, masih ada?');

    expect(Message::query()->count())->toBe(1);
});

it('validates empty message on send', function () {
    $buyer = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    $conversation = Conversation::factory()->withMembers($buyer, $seller)->create();

    Livewire::actingAs($buyer)
        ->test(ConversationDetail::class, ['conversation' => $conversation])
        ->set('newMessage', '')
        ->call('send')
        ->assertHasErrors(['newMessage']);
});

it('renders conversation list with empty state when user has no conversations', function () {
    $user = User::factory()->create(['role' => UserRole::User, 'status' => UserStatus::Active]);

    Livewire::actingAs($user)
        ->test(ConversationList::class)
        ->assertSee(__('chat.empty_conversations'))
        ->assertSee(__('chat.empty_conversations_desc'));
});

it('renders conversation list and filters by participant search', function () {
    $user = User::factory()->create(['name' => 'Budi User', 'role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller1 = User::factory()->create(['name' => 'Siti Penjual', 'role' => UserRole::User, 'status' => UserStatus::Active]);
    $seller2 = User::factory()->create(['name' => 'Andi Mobil', 'role' => UserRole::User, 'status' => UserStatus::Active]);

    $c1 = Conversation::factory()->withMembers($user, $seller1)->create();
    $c2 = Conversation::factory()->withMembers($user, $seller2)->create();

    app(SendMessage::class)->handle($seller1, $c1, 'Halo dari Siti');
    app(SendMessage::class)->handle($seller2, $c2, 'Halo dari Andi');

    Livewire::actingAs($user)
        ->test(ConversationList::class)
        ->assertSee('Siti Penjual')
        ->assertSee('Andi Mobil')
        ->set('search', 'Siti')
        ->assertSee('Siti Penjual')
        ->assertDontSee('Andi Mobil');
});
