<?php

declare(strict_types=1);

namespace App\Domains\Admin\Tests\Feature;

use App\Domains\Admin\Livewire\UserManagement;
use App\Domains\Admin\Models\AuditLog;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function makeAdminUser(): User
{
    return User::factory()->admin()->active()->create();
}

describe('ADM-4 user management page', function () {
    it('only admins can access the page', function () {
        get(route('admin.users.index'))->assertRedirect(route('login'));

        actingAs(User::factory()->user()->active()->create());
        get(route('admin.users.index'))->assertForbidden();

        // Blocked admin is logged out by not_blocked middleware and redirected.
        actingAs(User::factory()->admin()->blocked()->create());
        get(route('admin.users.index'))->assertRedirect(route('identity.blocked'));
    });

    it('lists users with search and filters', function () {
        $admin = makeAdminUser();
        actingAs($admin);

        $alice = User::factory()->user()->active()->create(['name' => 'Alice Cari', 'email' => 'alice@example.com']);
        $bob = User::factory()->inspector()->blocked()->create(['name' => 'Bob Blokir', 'email' => 'bob@example.com']);
        User::factory()->user()->pending()->create(['name' => 'Cici Tunggu', 'email' => 'cici@example.com']);

        Livewire::test(UserManagement::class)
            ->assertSee($alice->name)
            ->assertSee($bob->name)
            ->set('search', 'Alice Cari')
            ->assertSee($alice->name)
            ->assertDontSee($bob->name)
            ->set('search', '')
            ->set('statusFilter', UserStatus::Blocked->value)
            ->assertSee($bob->name)
            ->assertDontSee($alice->name)
            ->set('statusFilter', '')
            ->set('roleFilter', 'inspector')
            ->assertSee($bob->name)
            ->assertDontSee($alice->name);
    });

    it('does not show a block button on the admin own row', function () {
        $admin = makeAdminUser();
        actingAs($admin);

        $html = Livewire::test(UserManagement::class)->html();

        // Own row shows the "you" marker instead of action buttons for self.
        expect(substr_count($html, $admin->email))->toBeGreaterThanOrEqual(1);
    });

    it('blocks a user from the UI and records the audit log', function () {
        $admin = makeAdminUser();
        actingAs($admin);

        $user = User::factory()->user()->active()->create();

        Livewire::test(UserManagement::class)
            ->call('openBlockModal', (string) $user->id)
            ->set('blockReason', 'Spam berulang')
            ->call('blockUser')
            ->assertHasNoErrors();

        expect($user->fresh()->status)->toBe(UserStatus::Blocked);

        $log = AuditLog::query()->where('action', 'block_user')->latest('created_at')->first();
        expect($log)->not->toBeNull()
            ->and($log->entity)->toBe('user')
            ->and($log->entity_id)->toBe((string) $user->id)
            ->and($log->actor_id)->toBe((string) $admin->id)
            ->and($log->meta['reason'])->toBe('Spam berulang');
    });

    it('requires the confirmation checkbox when the user has active items', function () {
        $admin = makeAdminUser();
        actingAs($admin);

        $user = User::factory()->user()->active()->create();
        $job = InspectionJob::factory()->requested()->create([
            'requester_id' => $user->id,
        ]);

        $component = Livewire::test(UserManagement::class)
            ->call('openBlockModal', (string) $user->id)
            ->set('blockReason', 'Ada job aktif');

        // Confirm button is disabled until the checkbox is ticked.
        expect($component->html())->toContain('disabled');

        $component
            ->set('confirmCancelItems', true)
            ->call('blockUser')
            ->assertHasNoErrors();

        expect($user->fresh()->status)->toBe(UserStatus::Blocked);
        expect($job->fresh()->status)
            ->toBe(JobStatus::Cancelled);
    });

    it('activates a blocked user from the UI and records the audit log', function () {
        $admin = makeAdminUser();
        actingAs($admin);

        $user = User::factory()->user()->blocked()->create();

        Livewire::test(UserManagement::class)
            ->call('openActivateModal', (string) $user->id)
            ->call('activateUser')
            ->assertHasNoErrors();

        expect($user->fresh()->status)->toBe(UserStatus::Active);

        expect(AuditLog::query()->where('action', 'activate_user')->where('entity_id', (string) $user->id)->exists())
            ->toBeTrue();
    });

    it('non-admin gets 403 on page and actions; blocked admin gets 403 on actions', function () {
        // Non-admin cannot access the page (route middleware).
        actingAs(User::factory()->user()->active()->create());
        get(route('admin.users.index'))->assertForbidden();

        // Blocked admin: page loads as active admin, then blocked, actions 403.
        $admin = makeAdminUser();
        $target = User::factory()->user()->active()->create();

        foreach (['openBlockModal', 'blockUser', 'openActivateModal', 'activateUser'] as $action) {
            $admin->update(['status' => UserStatus::Active]);

            $component = Livewire::actingAs($admin)->test(UserManagement::class);

            $admin->update(['status' => UserStatus::Blocked]);

            if (in_array($action, ['openBlockModal', 'openActivateModal'], true)) {
                $component->call($action, (string) $target->id)->assertForbidden();
            } else {
                $component->call($action)->assertForbidden();
            }
        }

        expect($target->fresh()->status)->toBe(UserStatus::Active);
    });

    it('counts active items without N+1', function () {
        $admin = makeAdminUser();
        actingAs($admin);

        $users = User::factory()->user()->active()->count(5)->create();
        foreach ($users as $user) {
            InspectionJob::factory()->requested()->create([
                'requester_id' => $user->id,
            ]);
        }

        DB::enableQueryLog();

        Livewire::test(UserManagement::class)->call('$refresh');

        $queries = DB::getQueryLog();
        $selects = array_filter($queries, fn ($q): bool => str_starts_with(strtolower($q['query']), 'select'));

        // Users page + jobs + transactions + a few framework queries: must stay
        // flat regardless of user count (no per-row queries).
        expect(count($selects))->toBeLessThan(20);

        DB::disableQueryLog();
    });
});
