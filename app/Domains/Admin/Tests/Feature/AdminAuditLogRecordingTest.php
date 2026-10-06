<?php

declare(strict_types=1);

namespace App\Domains\Admin\Tests\Feature;

use App\Domains\Admin\Livewire\Settings;
use App\Domains\Admin\Models\AuditLog;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspector\Events\InspectorApproved;
use App\Domains\Inspector\Events\InspectorRejected;
use App\Domains\Listing\Events\ListingApproved;
use App\Domains\Listing\Events\ListingRejected;
use App\Domains\Result\Events\ResultReopened;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class AdminAuditLogRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_adm_t13_result_reopened_event_records_audit_log(): void
    {
        $admin = User::factory()->admin()->create();

        event(new ResultReopened(
            resultId: 'result-123',
            jobId: 'job-123',
            inspectorId: 'inspector-123',
            reopenedBy: (string) $admin->id,
            reason: 'Hasil perlu diperiksa ulang fotonya',
        ));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'reopen_result',
            'entity' => 'inspection_result',
            'entity_id' => 'result-123',
        ]);

        $log = AuditLog::query()->where('action', 'reopen_result')->firstOrFail();
        $this->assertSame('Hasil perlu diperiksa ulang fotonya', $log->meta['reason']);
        $this->assertSame('job-123', $log->meta['job_id']);
        $this->assertSame('inspector-123', $log->meta['inspector_id']);
    }

    public function test_adm_t13_admin_job_cancelled_event_records_audit_log(): void
    {
        $admin = User::factory()->admin()->create();

        event(new JobCancelled(
            jobId: 'job-456',
            listingId: 'listing-456',
            cancelledBy: (string) $admin->id,
            cancelReason: 'Permintaan admin karena penjual tidak kooperatif',
        ));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'cancel_job',
            'entity' => 'job',
            'entity_id' => 'job-456',
        ]);

        $log = AuditLog::query()->where('action', 'cancel_job')->firstOrFail();
        $this->assertSame('listing-456', $log->meta['listing_id']);
        $this->assertSame('Permintaan admin karena penjual tidak kooperatif', $log->meta['reason']);
    }

    public function test_adm_t13_non_admin_job_cancelled_event_does_not_record_admin_audit_log(): void
    {
        $user = User::factory()->create();

        event(new JobCancelled(
            jobId: 'job-789',
            listingId: 'listing-789',
            cancelledBy: (string) $user->id,
            cancelReason: 'Pemohon batal sendiri',
        ));

        $this->assertDatabaseMissing('audit_logs', [
            'entity_id' => 'job-789',
        ]);
    }

    public function test_adm_t13_inspector_approved_and_rejected_events_record_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $inspector1 = User::factory()->inspector()->create();
        $inspector2 = User::factory()->inspector()->create();

        event(new InspectorApproved(
            inspectorProfileId: 'profile-001',
            userId: (string) $inspector1->id,
            verifiedBy: (string) $admin->id,
        ));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'approve_inspector',
            'entity' => 'inspector_profile',
            'entity_id' => 'profile-001',
        ]);

        event(new InspectorRejected(
            inspectorProfileId: 'profile-002',
            userId: (string) $inspector2->id,
            rejectedBy: (string) $admin->id,
            reason: 'Dokumen KTP kadaluarsa',
        ));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'reject_inspector',
            'entity' => 'inspector_profile',
            'entity_id' => 'profile-002',
        ]);
    }

    public function test_adm_t13_listing_approved_and_rejected_events_record_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();

        event(new ListingApproved(
            listingId: 'listing-001',
            sellerId: 'seller-001',
            approvedBy: (string) $admin->id,
        ));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'approve_listing',
            'entity' => 'listing',
            'entity_id' => 'listing-001',
        ]);

        event(new ListingRejected(
            listingId: 'listing-002',
            sellerId: 'seller-002',
            rejectedBy: (string) $admin->id,
            reason: 'Foto kendaraan buram',
        ));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'reject_listing',
            'entity' => 'listing',
            'entity_id' => 'listing-002',
        ]);
    }

    public function test_adm_t13_settings_update_records_audit_log(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Settings::class)
            ->set('form.inspection_fee_car', 65000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'update_settings',
            'entity' => 'app_settings',
        ]);
    }
}
