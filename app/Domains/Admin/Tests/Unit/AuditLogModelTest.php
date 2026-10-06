<?php

declare(strict_types=1);

namespace App\Domains\Admin\Tests\Unit;

use App\Domains\Admin\Models\AuditLog;
use App\Domains\Identity\Models\User;
use BadMethodCallException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuditLogModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_can_be_created_and_has_actor_relation(): void
    {
        $admin = User::factory()->admin()->create();

        $log = AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'cancel_job',
            'entity' => 'job',
            'entity_id' => 'some-job-id',
            'meta' => ['reason' => 'Admin manual cancel'],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'action' => 'cancel_job',
            'entity' => 'job',
        ]);

        $this->assertNotNull($log->actor);
        $this->assertSame($admin->id, $log->actor->id);
        $this->assertSame(['reason' => 'Admin manual cancel'], $log->meta);
    }

    public function test_audit_log_is_immutable_and_cannot_be_updated(): void
    {
        $admin = User::factory()->admin()->create();

        $log = AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'update_settings',
            'entity' => 'app_settings',
            'entity_id' => null,
            'meta' => null,
        ]);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Audit log entries are immutable.');

        $log->update(['action' => 'hacked_action']);
    }

    public function test_audit_log_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();

        $log = AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'reopen_result',
            'entity' => 'inspection_result',
            'entity_id' => 'res-id',
            'meta' => ['reason' => 'Foto perlu diulang'],
        ]);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Audit log entries cannot be deleted.');

        $log->delete();
    }
}
