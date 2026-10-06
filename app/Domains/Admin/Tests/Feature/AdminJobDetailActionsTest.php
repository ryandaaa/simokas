<?php

declare(strict_types=1);

namespace App\Domains\Admin\Tests\Feature;

use App\Domains\Admin\Livewire\JobDetail;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class AdminJobDetailActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_adm_t01_job_detail_route_is_only_accessible_by_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => UserRole::User]);
        $inspector = User::factory()->create(['role' => UserRole::Inspector]);

        $job = InspectionJob::factory()->create();

        $this->actingAs($admin)->get("/admin/jobs/{$job->id}")->assertOk();
        $this->actingAs($user)->get("/admin/jobs/{$job->id}")->assertForbidden();
        $this->actingAs($inspector)->get("/admin/jobs/{$job->id}")->assertForbidden();
    }

    public function test_job_detail_shows_private_data_to_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $listing = Listing::factory()->active()->create([
            'address_full' => 'Jl. Kebon Jeruk No. 88, RT 01 RW 02',
            'chassis_number' => 'MH1JBA12345678901',
            'lat' => -6.1951,
            'lng' => 106.7725,
        ]);

        $job = InspectionJob::factory()->create([
            'listing_id' => $listing->id,
        ]);

        Livewire::actingAs($admin)
            ->test(JobDetail::class, ['job' => $job])
            ->assertSee('Jl. Kebon Jeruk No. 88, RT 01 RW 02')
            ->assertSee('MH1JBA12345678901')
            ->assertSee((string) $listing->lat)
            ->assertSee((string) $listing->lng);
    }

    public function test_adm_t08_admin_cancels_job_with_mandatory_reason_and_refund(): void
    {
        $admin = User::factory()->admin()->create();
        $requester = User::factory()->create();
        $job = InspectionJob::factory()->create([
            'requester_id' => $requester->id,
            'status' => JobStatus::Taken,
        ]);

        $payment = Payment::create([
            'payer_id' => $requester->id,
            'purpose' => PaymentPurpose::Inspection,
            'amount' => 50000,
            'status' => PaymentStatus::Success,
            'ref_id' => $job->id,
            'idempotency_key' => 'payment-test-cancel-01',
        ]);

        // Validation error on empty reason
        Livewire::actingAs($admin)
            ->test(JobDetail::class, ['job' => $job])
            ->set('cancelReason', '')
            ->call('cancelJob')
            ->assertHasErrors(['cancelReason' => 'required']);

        // Cancel with valid reason
        Livewire::actingAs($admin)
            ->test(JobDetail::class, ['job' => $job])
            ->set('cancelReason', 'Inspektor tidak datang ke lokasi setelah 48 jam')
            ->set('refund', true)
            ->call('cancelJob')
            ->assertHasNoErrors()
            ->assertSee(__('admin.jobs.cancel_success'));

        $fresh = $job->fresh();
        $this->assertSame(JobStatus::Cancelled, $fresh->status);
        $this->assertSame('Inspektor tidak datang ke lokasi setelah 48 jam', $fresh->cancel_reason);

        // Refund payment check
        $this->assertSame(PaymentStatus::Refunded, $payment->fresh()->status);

        // Audit log check (ADM-T13)
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'cancel_job',
            'entity' => 'job',
            'entity_id' => (string) $job->id,
        ]);
    }

    public function test_adm_t07_admin_manually_completes_result_submitted_job_and_releases_fee(): void
    {
        $admin = User::factory()->admin()->create();
        $inspector = User::factory()->inspector()->create();
        $requester = User::factory()->create();

        $job = InspectionJob::factory()->create([
            'requester_id' => $requester->id,
            'inspector_id' => $inspector->id,
            'status' => JobStatus::ResultSubmitted,
            'fee_amount' => 50000,
            'platform_fee_pct' => 10,
        ]);

        $payment = Payment::create([
            'payer_id' => $requester->id,
            'purpose' => PaymentPurpose::Inspection,
            'amount' => 50000,
            'status' => PaymentStatus::Success,
            'ref_id' => $job->id,
            'idempotency_key' => 'payment-test-complete-01',
        ]);

        Livewire::actingAs($admin)
            ->test(JobDetail::class, ['job' => $job])
            ->call('completeJob')
            ->assertHasNoErrors()
            ->assertSee(__('admin.jobs.complete_success'));

        $fresh = $job->fresh();
        $this->assertSame(JobStatus::Completed, $fresh->status);
        $this->assertNotNull($fresh->completed_at);

        // Fee paid to inspector ledger entry
        $this->assertDatabaseHas('ledger_entries', [
            'payment_id' => $payment->id,
            'entry_type' => LedgerEntryType::InspectionFee->value,
            'account_user_id' => $inspector->id,
            'amount' => 45000,
        ]);

        // Audit log check (ADM-T13)
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'complete_job',
            'entity' => 'job',
            'entity_id' => (string) $job->id,
        ]);
    }

    public function test_adm_t09_admin_reopens_submitted_inspection_result(): void
    {
        $admin = User::factory()->admin()->create();
        $inspector = User::factory()->inspector()->create();

        $template = InspectionTemplate::factory()->create();

        $job = InspectionJob::factory()->create([
            'inspector_id' => $inspector->id,
            'status' => JobStatus::ResultSubmitted,
        ]);

        $result = InspectionResult::factory()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Submitted,
        ]);

        // Reason is required
        Livewire::actingAs($admin)
            ->test(JobDetail::class, ['job' => $job])
            ->set('reopenReason', '')
            ->call('reopenResult')
            ->assertHasErrors(['reopenReason' => 'required']);

        // Reopen with valid reason
        Livewire::actingAs($admin)
            ->test(JobDetail::class, ['job' => $job])
            ->set('reopenReason', 'Foto mesin kurang jelas dan catatan rem perlu dilengkapi')
            ->call('reopenResult')
            ->assertHasNoErrors()
            ->assertSee(__('admin.jobs.reopen_success'));

        $this->assertSame(ResultStatus::Draft, $result->fresh()->status);
        $this->assertSame('Foto mesin kurang jelas dan catatan rem perlu dilengkapi', $result->fresh()->reopen_reason);
        $this->assertSame(JobStatus::InProgress, $job->fresh()->status);

        // Audit log recorded via ResultReopened event listener (ADM-T13)
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'reopen_result',
            'entity' => 'inspection_result',
            'entity_id' => (string) $result->id,
        ]);
    }

    public function test_adm_t04_double_action_on_the_same_job_is_idempotent_and_effects_once(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        $job = InspectionJob::factory()->create([
            'status' => JobStatus::Taken,
        ]);

        // First admin cancels
        Livewire::actingAs($admin1)
            ->test(JobDetail::class, ['job' => $job])
            ->set('cancelReason', 'Alasan pertama')
            ->call('cancelJob')
            ->assertSee(__('admin.jobs.cancel_success'));

        $this->assertSame(JobStatus::Cancelled, $job->fresh()->status);

        // Second admin cancels again: handled idempotently without error
        Livewire::actingAs($admin2)
            ->test(JobDetail::class, ['job' => $job])
            ->set('cancelReason', 'Alasan kedua')
            ->call('cancelJob');

        /** @var InspectionJob $freshJob */
        $freshJob = InspectionJob::query()->findOrFail($job->id);
        $this->assertSame('Alasan pertama', $freshJob->cancel_reason);
    }

    public function test_conflict_when_status_already_changed_shows_friendly_message(): void
    {
        $admin = User::factory()->admin()->create();

        $job = InspectionJob::factory()->create([
            'status' => JobStatus::ResultSubmitted,
        ]);

        $component = Livewire::actingAs($admin)
            ->test(JobDetail::class, ['job' => $job]);

        // Concurrent update changes the job status in database behind the scenes
        InspectionJob::query()->whereKey($job->id)->update([
            'status' => JobStatus::Cancelled->value,
        ]);

        // Attempting to complete the job throws 409 conflict caught with friendly message
        $component->call('completeJob')
            ->assertSee(__('admin.jobs.already_processed'));
    }

    public function test_adm_t02_non_admin_gets_403_on_mutating_actions_without_state_effect(): void
    {
        $user = User::factory()->create(['role' => UserRole::User]);

        $job = InspectionJob::factory()->create([
            'status' => JobStatus::Taken,
        ]);

        // Mount itself refuses non-admins
        Livewire::actingAs($user)
            ->test(JobDetail::class, ['job' => $job])
            ->assertForbidden();

        $this->assertSame(JobStatus::Taken, $job->fresh()->status);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_adm_t02_blocked_admin_gets_403_on_mutating_actions_without_state_effect(): void
    {
        $blockedAdmin = User::factory()->admin()->create([
            'status' => UserStatus::Blocked,
        ]);

        $job = InspectionJob::factory()->create([
            'status' => JobStatus::Taken,
        ]);

        Livewire::actingAs($blockedAdmin)
            ->test(JobDetail::class, ['job' => $job])
            ->assertForbidden();

        $this->assertSame(JobStatus::Taken, $job->fresh()->status);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_adm_t02_admin_blocked_mid_session_cannot_cancel_complete_or_reopen(): void
    {
        $admin = User::factory()->admin()->create();
        $inspector = User::factory()->inspector()->create();
        $template = InspectionTemplate::factory()->create();

        $job = InspectionJob::factory()->create([
            'inspector_id' => $inspector->id,
            'status' => JobStatus::ResultSubmitted,
        ]);

        $result = InspectionResult::factory()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Submitted,
        ]);

        // Admin gets blocked after the page loads; Livewire actions must re-check.
        // Each action gets a fresh mount as active admin (mount passes), then the
        // admin is blocked, then the action must 403. A 403 response does not
        // return a reusable Livewire snapshot, hence one component per action.
        foreach (['cancelJob', 'completeJob', 'reopenResult'] as $action) {
            $admin->update(['status' => UserStatus::Active]);

            $component = Livewire::actingAs($admin)
                ->test(JobDetail::class, ['job' => $job]);

            $admin->update(['status' => UserStatus::Blocked]);

            if ($action === 'cancelJob') {
                $component->set('cancelReason', 'Alasan jahat');
            }

            if ($action === 'reopenResult') {
                $component->set('reopenReason', 'Alasan jahat');
            }

            $component->call($action)->assertForbidden();
        }

        // No state or data effect
        $this->assertSame(JobStatus::ResultSubmitted, $job->fresh()->status);
        $this->assertSame(ResultStatus::Submitted, $result->fresh()->status);
        $this->assertDatabaseCount('audit_logs', 0);
    }
}
