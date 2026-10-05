<?php

declare(strict_types=1);

use App\Domains\Identity\Livewire\Blocked;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('renders blocked page and allows user to logout', function () {
    $user = User::factory()->blocked()->create();

    actingAs($user);

    Livewire::test(Blocked::class)
        ->assertSee(__('identity.blocked.title'))
        ->assertSee(__('identity.blocked.message'))
        ->call('logout')
        ->assertRedirect(route('login'));

    expect(Auth::check())->toBeFalse();
});

it('returns 403 json response when blocked user makes json request', function () {
    $user = User::factory()->blocked()->create();

    actingAs($user);

    getJson(route('dashboard'))
        ->assertStatus(403)
        ->assertJson([
            'message' => __('identity.login.blocked_message'),
        ]);
});
