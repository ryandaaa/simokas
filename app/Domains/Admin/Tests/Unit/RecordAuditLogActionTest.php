<?php

declare(strict_types=1);

namespace App\Domains\Admin\Tests\Unit;

use App\Domains\Admin\Actions\RecordAuditLog;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RecordAuditLogActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_adm_t13_record_audit_log_action_records_sensitive_actions_with_actor_action_entity_id_meta(): void
    {
        $admin = User::factory()->admin()->create();
        $action = new RecordAuditLog();

        $log = $action->handle(
            actor: $admin,
            action: 'cancel_job',
            entity: 'job',
            entityId: 'job-123',
            meta: [
                'reason' => 'Customer requested cancellation directly to admin',
                'refund' => true,
            ],
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'actor_id' => $admin->id,
            'action' => 'cancel_job',
            'entity' => 'job',
            'entity_id' => 'job-123',
        ]);

        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame('cancel_job', $log->action);
        $this->assertSame('job', $log->entity);
        $this->assertSame('job-123', $log->entity_id);
        $this->assertSame([
            'reason' => 'Customer requested cancellation directly to admin',
            'refund' => true,
        ], $log->meta);
        $this->assertNotNull($log->created_at);
    }
}
