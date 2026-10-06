<?php

declare(strict_types=1);

namespace App\Domains\Admin\Tests\Feature;

use App\Domains\Admin\Livewire\UnifiedAdminQueue;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

final class AdminUnifiedQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_adm_t01_unified_queue_route_is_only_accessible_by_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => UserRole::User]);
        $inspector = User::factory()->create(['role' => UserRole::Inspector]);

        $this->actingAs($admin)->get('/admin/queue')->assertOk();
        $this->actingAs($user)->get('/admin/queue')->assertForbidden();
        $this->actingAs($inspector)->get('/admin/queue')->assertForbidden();
    }

    public function test_admin_index_redirects_to_admin_queue(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('admin.queue.index'));
    }

    public function test_unauthenticated_user_is_redirected_to_login_from_unified_queue(): void
    {
        $this->get('/admin/queue')->assertRedirect(route('login'));
    }

    public function test_renders_empty_state_when_all_queues_are_clear(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(UnifiedAdminQueue::class)
            ->assertSee(__('admin.unified_queue.empty_title'))
            ->assertSet('totalCount', 0)
            ->assertSet('listingCount', 0)
            ->assertSet('inspectorCount', 0)
            ->assertSet('jobCount', 0);
    }

    public function test_lists_items_from_all_three_sources_oldest_first(): void
    {
        $admin = User::factory()->admin()->create();

        // 1. Pending Listing (5 days ago)
        $listing = Listing::factory()->pendingReview()->create([
            'title' => 'Toyota Yaris Tertua',
            'created_at' => now()->subDays(5),
        ]);

        // 2. Pending Inspector (3 days ago)
        $inspectorUser = User::factory()->inspector()->create(['name' => 'Budi Inspektor Menengah']);
        $profile = InspectorProfile::factory()->create([
            'user_id' => $inspectorUser->id,
            'verification_status' => VerificationStatus::Pending,
            'created_at' => now()->subDays(3),
        ]);
        InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Ktp]);
        InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Certificate]);
        InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Cv]);
        InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Portfolio]);

        // 3. Job needing attention (1 day ago)
        $seller = User::factory()->create();
        $jobListing = Listing::factory()->active()->create(['seller_id' => $seller->id, 'title' => 'Honda Jazz Bermasalah']);
        $job = InspectionJob::factory()->create([
            'listing_id' => $jobListing->id,
            'status' => JobStatus::Taken,
            'needs_attention_at' => now()->subDay(),
            'created_at' => now()->subDays(2),
        ]);

        $component = Livewire::actingAs($admin)
            ->test(UnifiedAdminQueue::class)
            ->assertSet('totalCount', 3)
            ->assertSet('listingCount', 1)
            ->assertSet('inspectorCount', 1)
            ->assertSet('jobCount', 1)
            ->assertSee('Toyota Yaris Tertua')
            ->assertSee('Budi Inspektor Menengah')
            ->assertSee('Honda Jazz Bermasalah');

        $html = $component->html();
        // Oldest first: Toyota Yaris (5 days ago) before Budi (3 days ago) before Honda Jazz (1 day ago)
        $posListing = strpos($html, 'Toyota Yaris Tertua');
        $posInspector = strpos($html, 'Budi Inspektor Menengah');
        $posJob = strpos($html, 'Honda Jazz Bermasalah');

        $this->assertTrue($posListing < $posInspector, 'Listing should appear before Inspector');
        $this->assertTrue($posInspector < $posJob, 'Inspector should appear before Job');
    }

    public function test_filters_by_type(): void
    {
        $admin = User::factory()->admin()->create();

        Listing::factory()->pendingReview()->create(['title' => 'Toyota Kijang Innova']);

        $inspectorUser = User::factory()->inspector()->create(['name' => 'Ahmad Inspektor']);
        InspectorProfile::factory()->create([
            'user_id' => $inspectorUser->id,
            'verification_status' => VerificationStatus::Pending,
        ]);
        InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Ktp]);
        InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Certificate]);
        InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Cv]);
        InspectorDocument::factory()->create(['user_id' => $inspectorUser->id, 'type' => DocumentType::Portfolio]);

        $jobListing = Listing::factory()->active()->create(['title' => 'Suzuki Baleno Active Vehicle']);
        InspectionJob::factory()->create([
            'listing_id' => $jobListing->id,
            'status' => JobStatus::Taken,
            'needs_attention_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(UnifiedAdminQueue::class)
            ->set('filterType', 'listing')
            ->assertSee('Toyota Kijang Innova')
            ->assertDontSee('Ahmad Inspektor')
            ->assertDontSee('Suzuki Baleno Active Vehicle')
            ->set('filterType', 'inspector')
            ->assertSee('Ahmad Inspektor')
            ->assertDontSee('Toyota Kijang Innova')
            ->assertDontSee('Suzuki Baleno Active Vehicle')
            ->set('filterType', 'job')
            ->assertSee('Suzuki Baleno Active Vehicle')
            ->assertDontSee('Toyota Kijang Innova')
            ->assertDontSee('Ahmad Inspektor');
    }

    public function test_unified_queue_has_no_n_plus_one_queries(): void
    {
        $admin = User::factory()->admin()->create();

        // Seed 5 listings
        for ($i = 0; $i < 5; $i++) {
            Listing::factory()->pendingReview()->create(['title' => "Listing {$i}"]);
        }

        // Seed 5 inspectors
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->inspector()->create();
            InspectorProfile::factory()->create([
                'user_id' => $user->id,
                'verification_status' => VerificationStatus::Pending,
            ]);
            InspectorDocument::factory()->create(['user_id' => $user->id, 'type' => DocumentType::Ktp]);
            InspectorDocument::factory()->create(['user_id' => $user->id, 'type' => DocumentType::Certificate]);
            InspectorDocument::factory()->create(['user_id' => $user->id, 'type' => DocumentType::Cv]);
            InspectorDocument::factory()->create(['user_id' => $user->id, 'type' => DocumentType::Portfolio]);
        }

        // Seed 5 jobs
        for ($i = 0; $i < 5; $i++) {
            $lst = Listing::factory()->active()->create();
            InspectionJob::factory()->create([
                'listing_id' => $lst->id,
                'status' => JobStatus::Taken,
                'needs_attention_at' => now(),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::actingAs($admin)
            ->test(UnifiedAdminQueue::class)
            ->assertViewHas('totalCount', 15);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // 3 count queries + 3 lightweight ID select queries + eager loading queries (models + relations)
        // With items across 3 sources and 8 relations, total queries is constant (15 queries), well below N+1 (>45 queries)
        $this->assertLessThanOrEqual(16, count($queries), 'Query count exceeds expected limit, possible N+1 query problem.');
    }
}
