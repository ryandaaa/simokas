<?php

declare(strict_types=1);

namespace App\Domains\Admin\Tests\Feature;

use App\Domains\Admin\Livewire\AuditLogIndex;
use App\Domains\Admin\Models\AuditLog;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class AdminAuditLogIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_adm_t01_audit_log_route_is_only_accessible_by_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => UserRole::User]);
        $inspector = User::factory()->create(['role' => UserRole::Inspector]);

        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk();
        $this->actingAs($user)->get('/admin/audit-logs')->assertForbidden();
        $this->actingAs($inspector)->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login_from_audit_logs(): void
    {
        $this->get('/admin/audit-logs')->assertRedirect(route('login'));
    }

    public function test_renders_empty_state_when_no_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AuditLogIndex::class)
            ->assertSee(__('admin.audit_logs.empty_title'));
    }

    public function test_lists_audit_logs_with_actor_and_action(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Bambang Admin']);

        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'cancel_job',
            'entity' => 'job',
            'entity_id' => 'job-uuid-1234',
            'meta' => ['reason' => 'Listing melanggar aturan'],
        ]);

        Livewire::actingAs($admin)
            ->test(AuditLogIndex::class)
            ->assertSee('Bambang Admin')
            ->assertSee(__('admin.audit_logs.actions.cancel_job'))
            ->assertSee('job-uuid-1234')
            ->assertSee('Listing melanggar aturan');
    }

    public function test_filters_audit_logs_by_action_and_entity(): void
    {
        $admin = User::factory()->admin()->create();

        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'cancel_job',
            'entity' => 'job',
            'entity_id' => 'job-001',
        ]);

        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'approve_listing',
            'entity' => 'listing',
            'entity_id' => 'listing-001',
        ]);

        Livewire::actingAs($admin)
            ->test(AuditLogIndex::class)
            ->set('filterAction', 'cancel_job')
            ->assertSee('job-001')
            ->assertDontSee('listing-001')
            ->set('filterAction', 'approve_listing')
            ->assertSee('listing-001')
            ->assertDontSee('job-001')
            ->set('filterAction', '')
            ->set('filterEntity', 'job')
            ->assertSee('job-001')
            ->assertDontSee('listing-001');
    }

    public function test_searches_audit_logs_by_entity_id_or_actor(): void
    {
        $admin1 = User::factory()->admin()->create(['name' => 'Super Admin Alpha']);
        $admin2 = User::factory()->admin()->create(['name' => 'Support Admin Beta']);

        AuditLog::create([
            'actor_id' => $admin1->id,
            'action' => 'cancel_job',
            'entity' => 'job',
            'entity_id' => 'target-target-xyz',
        ]);

        AuditLog::create([
            'actor_id' => $admin2->id,
            'action' => 'update_settings',
            'entity' => 'app_settings',
            'entity_id' => 'setting-abc',
        ]);

        Livewire::actingAs($admin1)
            ->test(AuditLogIndex::class)
            ->set('search', 'Alpha')
            ->assertSee('target-target-xyz')
            ->assertDontSee('setting-abc')
            ->set('search', 'setting-abc')
            ->assertSee('setting-abc')
            ->assertDontSee('target-target-xyz');
    }
}
