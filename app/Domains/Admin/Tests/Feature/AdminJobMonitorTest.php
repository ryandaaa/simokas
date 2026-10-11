<?php

declare(strict_types=1);

namespace App\Domains\Admin\Tests\Feature;

use App\Domains\Admin\Livewire\JobMonitor;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class AdminJobMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_adm_t01_job_monitor_route_is_only_accessible_by_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => UserRole::User]);
        $inspector = User::factory()->create(['role' => UserRole::Inspector]);

        $this->actingAs($admin)->get('/admin/jobs')->assertOk();
        $this->actingAs($user)->get('/admin/jobs')->assertForbidden();
        $this->actingAs($inspector)->get('/admin/jobs')->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login_from_job_monitor(): void
    {
        $this->get('/admin/jobs')->assertRedirect(route('login'));
    }

    public function test_renders_empty_state_when_no_jobs(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(JobMonitor::class)
            ->assertSee(__('admin.jobs.empty_title'));
    }

    public function test_lists_jobs_with_vehicle_requester_inspector_status(): void
    {
        $admin = User::factory()->admin()->create();
        $requester = User::factory()->create(['name' => 'Charlie Pemohon']);
        $inspector = User::factory()->inspector()->create(['name' => 'Doni Inspektor']);
        $listing = Listing::factory()->active()->create(['title' => 'Mitsubishi Xpander 2023']);

        $job = InspectionJob::factory()->create([
            'listing_id' => $listing->id,
            'requester_id' => $requester->id,
            'inspector_id' => $inspector->id,
            'status' => JobStatus::InProgress,
        ]);

        Livewire::actingAs($admin)
            ->test(JobMonitor::class)
            ->assertSee('Mitsubishi Xpander 2023')
            ->assertSee('Charlie Pemohon')
            ->assertSee('Doni Inspektor')
            ->assertSee($job->status->label())
            ->assertSee(route('admin.jobs.show', $job->id));
    }

    public function test_filters_jobs_by_status_and_attention(): void
    {
        $admin = User::factory()->admin()->create();
        $listing1 = Listing::factory()->active()->create(['title' => 'Car One']);
        $listing2 = Listing::factory()->active()->create(['title' => 'Car Two']);
        $listing3 = Listing::factory()->active()->create(['title' => 'Car Waiting Confirmation']);

        $job1 = InspectionJob::factory()->create([
            'listing_id' => $listing1->id,
            'status' => JobStatus::InProgress,
        ]);

        $job2 = InspectionJob::factory()->create([
            'listing_id' => $listing2->id,
            'status' => JobStatus::Completed,
            'needs_attention_at' => null,
        ]);

        InspectionJob::factory()->create([
            'listing_id' => $listing3->id,
            'status' => JobStatus::ResultSubmitted,
            'submitted_at' => now(),
            'needs_attention_at' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(JobMonitor::class)
            ->set('filterStatus', JobStatus::InProgress->value)
            ->assertSee('Car One')
            ->assertDontSee('Car Two')
            ->set('filterStatus', JobStatus::Completed->value)
            ->assertSee('Car Two')
            ->assertDontSee('Car One')
            ->set('filterStatus', '')
            ->set('filterAttentionOnly', true)
            ->assertSee('Car Waiting Confirmation')
            ->assertDontSee('Car One')
            ->assertDontSee('Car Two');
    }

    public function test_searches_jobs_by_title_or_id(): void
    {
        $admin = User::factory()->admin()->create();
        $listingA = Listing::factory()->active()->create(['title' => 'Honda CR-V Spesial']);
        $listingB = Listing::factory()->active()->create(['title' => 'Nissan Grand Livina']);

        InspectionJob::factory()->create(['listing_id' => $listingA->id]);
        InspectionJob::factory()->create(['listing_id' => $listingB->id]);

        Livewire::actingAs($admin)
            ->test(JobMonitor::class)
            ->set('search', 'CR-V')
            ->assertSee('Honda CR-V Spesial')
            ->assertDontSee('Nissan Grand Livina')
            ->set('search', 'Livina')
            ->assertSee('Nissan Grand Livina')
            ->assertDontSee('Honda CR-V Spesial');
    }
}
