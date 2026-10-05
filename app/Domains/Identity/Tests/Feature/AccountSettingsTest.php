<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Livewire\AccountSettings;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('AUTH-T13 allows authenticated user to update name and phone', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'name' => 'Nama Awal',
        'email' => 'user@example.com',
        'phone' => '081234567890',
        'role' => UserRole::User,
        'status' => UserStatus::Active,
    ]);

    actingAs($user);

    Livewire::test(AccountSettings::class)
        ->assertSet('name', 'Nama Awal')
        ->assertSet('email', 'user@example.com')
        ->assertSet('phone', '081234567890')
        ->set('name', 'Nama Baru')
        ->set('phone', '089876543210')
        ->call('updateProfile')
        ->assertHasNoErrors()
        ->assertSee(__('identity.account.profile_updated'));

    $user->refresh();
    expect($user->name)->toBe('Nama Baru')
        ->and($user->phone)->toBe('089876543210')
        ->and($user->email)->toBe('user@example.com');
});

it('AUTH-T13 allows authenticated user to change password', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('PasswordLama123!'),
    ]);

    actingAs($user);

    Livewire::test(AccountSettings::class)
        ->set('current_password', 'PasswordLama123!')
        ->set('password', 'PasswordBaru123!')
        ->set('password_confirmation', 'PasswordBaru123!')
        ->call('changePassword')
        ->assertHasNoErrors()
        ->assertSee(__('identity.account.password_changed'));

    // Current session remains authenticated (AUTH-E10)
    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);

    $user->refresh();
    expect(Hash::check('PasswordBaru123!', $user->password))->toBeTrue();
});

it('AUTH-T13 rejects invalid phone number format on profile update', function (string $phone) {
    /** @var User $user */
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(AccountSettings::class)
        ->set('name', 'Budi Santoso')
        ->set('phone', $phone)
        ->call('updateProfile')
        ->assertHasErrors(['phone']);
})->with([
    'too short (less than 9 digits)' => ['081234'],
    'not indonesian format (+1 prefix)' => ['+14155552671'],
    'contains letters' => ['0812abc3456'],
    'invalid prefix' => ['0712345678'],
    'empty phone' => [''],
]);

it('AUTH-T13 rejects weak or unconfirmed new password on change password', function (string $password, string $confirmation) {
    /** @var User $user */
    $user = User::factory()->create([
        'password' => Hash::make('PasswordLama123!'),
    ]);

    actingAs($user);

    Livewire::test(AccountSettings::class)
        ->set('current_password', 'PasswordLama123!')
        ->set('password', $password)
        ->set('password_confirmation', $confirmation)
        ->call('changePassword')
        ->assertHasErrors(['password']);
})->with([
    'too short (less than 8)' => ['Ab1!', 'Ab1!'],
    'only letters' => ['PasswordOnly', 'PasswordOnly'],
    'only numbers' => ['1234567890', '1234567890'],
    'unconfirmed' => ['PasswordBaru123!', 'MismatchPassword123!'],
]);

it('AUTH-T13 rejects change password when current password is incorrect', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'password' => Hash::make('PasswordBenar123!'),
    ]);

    actingAs($user);

    Livewire::test(AccountSettings::class)
        ->set('current_password', 'PasswordTebakanSalah123!')
        ->set('password', 'PasswordBaru123!')
        ->set('password_confirmation', 'PasswordBaru123!')
        ->call('changePassword')
        ->assertHasErrors(['current_password']);

    $user->refresh();
    expect(Hash::check('PasswordBenar123!', $user->password))->toBeTrue();
});

it('AUTH-T13 rate limits change password against repeated wrong password attempts', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'password' => Hash::make('PasswordBenar123!'),
    ]);

    actingAs($user);

    Carbon::setTestNow(now());

    // 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        Livewire::test(AccountSettings::class)
            ->set('current_password', 'PasswordSalah'.$i)
            ->set('password', 'PasswordBaru123!')
            ->set('password_confirmation', 'PasswordBaru123!')
            ->call('changePassword')
            ->assertHasErrors(['current_password']);
    }

    // 6th attempt should be blocked with 429
    Livewire::test(AccountSettings::class)
        ->set('current_password', 'PasswordSalah6')
        ->set('password', 'PasswordBaru123!')
        ->set('password_confirmation', 'PasswordBaru123!')
        ->call('changePassword')
        ->assertStatus(429);

    // After throttle period (61s), user can attempt again
    Carbon::setTestNow(now()->addSeconds(61));

    Livewire::test(AccountSettings::class)
        ->set('current_password', 'PasswordBenar123!')
        ->set('password', 'PasswordBaru123!')
        ->set('password_confirmation', 'PasswordBaru123!')
        ->call('changePassword')
        ->assertHasNoErrors()
        ->assertSee(__('identity.account.password_changed'));

    Carbon::setTestNow();
});

it('AUTH-T13 rejects unauthenticated access to account settings route', function () {
    $response = get(route('identity.account'));

    $response->assertRedirect(route('login'));
});

it('AUTH-T13 rejects blocked user from accessing account settings', function () {
    /** @var User $blockedUser */
    $blockedUser = User::factory()->blocked()->create();

    actingAs($blockedUser);

    $response = get(route('identity.account'));

    $response->assertRedirect(route('identity.blocked'));
    expect(Auth::check())->toBeFalse();
});

it('AUTH-T13 allows user, inspector, and admin roles to access account settings', function (string $role) {
    /** @var User $user */
    $user = User::factory()->create([
        'role' => UserRole::from($role),
        'status' => UserStatus::Active,
    ]);

    actingAs($user);

    $response = get(route('identity.account'));

    $response->assertOk();
    $response->assertSee(__('identity.account.title'));
})->with([
    'user role' => ['user'],
    'inspector role' => ['inspector'],
    'admin role' => ['admin'],
]);

it('AUTH-T13 does not modify email, role, or status through profile update form manipulation', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'name' => 'Nama Asli',
        'email' => 'original@example.com',
        'phone' => '081234567890',
        'role' => UserRole::User,
        'status' => UserStatus::Active,
    ]);

    actingAs($user);

    Livewire::test(AccountSettings::class)
        ->set('name', 'Nama Diperbarui')
        ->set('phone', '089876543210')
        ->set('email', 'hacked@example.com')
        ->call('updateProfile')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Nama Diperbarui')
        ->and($user->phone)->toBe('089876543210')
        ->and($user->email)->toBe('original@example.com')
        ->and($user->role)->toBe(UserRole::User)
        ->and($user->status)->toBe(UserStatus::Active);
});

it('AUTH-T13 displays email as disabled with an explanation that email cannot be changed', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'email' => 'fixed@example.com',
    ]);

    actingAs($user);

    Livewire::test(AccountSettings::class)
        ->assertSee('fixed@example.com')
        ->assertSee(__('identity.account.email_hint'));
});

it('AUTH-T13 prevents user from updating another user account via policy', function () {
    /** @var User $user1 */
    $user1 = User::factory()->create();
    /** @var User $user2 */
    $user2 = User::factory()->create();
    /** @var User $admin */
    $admin = User::factory()->admin()->create();

    actingAs($user1);

    expect(Gate::allows('updateProfile', $user2))->toBeFalse()
        ->and(Gate::allows('changePassword', $user2))->toBeFalse()
        ->and(Gate::allows('updateProfile', $user1))->toBeTrue()
        ->and(Gate::allows('changePassword', $user1))->toBeTrue();

    // Admin also cannot use self-service account actions on other users
    actingAs($admin);
    expect(Gate::allows('updateProfile', $user1))->toBeFalse()
        ->and(Gate::allows('changePassword', $user1))->toBeFalse();
});

it('AUTH-T13 renders loading indicators and error states in account settings component', function () {
    /** @var User $user */
    $user = User::factory()->create();

    actingAs($user);

    // Initial render has wire:loading targets
    Livewire::test(AccountSettings::class)
        ->assertSeeHtml('wire:target="updateProfile"')
        ->assertSeeHtml('wire:target="changePassword"');

    // Trigger validation errors and assert they are shown
    Livewire::test(AccountSettings::class)
        ->set('name', '')
        ->set('phone', '')
        ->call('updateProfile')
        ->assertHasErrors(['name', 'phone']);

    Livewire::test(AccountSettings::class)
        ->set('current_password', '')
        ->set('password', '')
        ->call('changePassword')
        ->assertHasErrors(['current_password', 'password']);
});
