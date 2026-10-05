<?php

declare(strict_types=1);

use App\Domains\Identity\Livewire\Dashboard;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('redirects unauthenticated users from dashboard to login', function () {
    get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('allows authenticated users to view dashboard', function () {
    $user = User::factory()->active()->create([
        'name' => 'Budi Pengguna',
    ]);

    actingAs($user);

    get(route('dashboard'))
        ->assertOk()
        ->assertSee('Budi Pengguna')
        ->assertSee(__('identity.dashboard.notice'));
});

it('allows user to logout from dashboard', function () {
    $user = User::factory()->active()->create();

    actingAs($user);

    Livewire::test(Dashboard::class)
        ->call('logout')
        ->assertRedirect(route('login'));

    expect(Auth::check())->toBeFalse();
});
