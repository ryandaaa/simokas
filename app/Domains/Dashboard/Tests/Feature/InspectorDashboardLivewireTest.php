<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Tests\Feature;

use App\Domains\Dashboard\DTOs\InspectorDashboardSummary;
use App\Domains\Dashboard\Livewire\InspectorDashboard;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createLivewireInspector(string $name = 'Livewire Inspector'): User
{
    $user = User::factory()->create([
        'name' => $name,
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    InspectorProfile::factory()->for($user, 'user')->create([
        'verification_status' => VerificationStatus::Approved,
        'rating_avg' => 4.9,
        'rating_count' => 15,
        'vehicle_types' => ['car', 'motorcycle'],
    ]);

    return $user;
}

it('DSH-T01 passes all new reader variables and legacy variables to the view', function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
    $inspector = createLivewireInspector();

    Livewire::test(InspectorDashboard::class, ['user' => $inspector])
        ->assertViewHas('summary', fn ($summary) => $summary instanceof InspectorDashboardSummary)
        ->assertViewHas('tasks', fn ($tasks) => is_array($tasks))
        ->assertViewHas('earningsBreakdown', fn ($breakdown) => is_array($breakdown))
        ->assertViewHas('trendSeries', fn ($trend) => is_array($trend) && count($trend) === 6)
        ->assertViewHas('selectedMonth', fn ($m) => $m instanceof CarbonImmutable && $m->format('Y-m') === '2026-10')
        ->assertViewHas('canGoNext', false)
        // Legacy variables preserved
        ->assertViewHas('availableJobs', 0)
        ->assertViewHas('activeJobs', 0)
        ->assertViewHas('netEarnings', 0)
        ->assertViewHas('totalInspections', 0)
        ->assertViewHas('rating', 4.9)
        ->assertViewHas('ratingCount', 15)
        ->assertViewHas('recentJobs')
        ->assertViewHas('monthlySeries', fn ($series) => is_array($series) && count($series) === 6 && isset($series[0]['value'], $series[0]['label']))
        ->assertViewHas('user', $inspector);
});

it('DSH-T01 falls back to current month when month query parameter is invalid', function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
    $inspector = createLivewireInspector();

    Livewire::withQueryParams(['bulan' => 'invalid-month'])
        ->test(InspectorDashboard::class, ['user' => $inspector])
        ->assertSet('month', '')
        ->assertViewHas('selectedMonth', fn (CarbonImmutable $m) => $m->format('Y-m') === '2026-10')
        ->assertViewHas('canGoNext', false);
});

it('DSH-T01 falls back to current month when month query parameter is in the future', function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
    $inspector = createLivewireInspector();

    Livewire::withQueryParams(['bulan' => '2099-01'])
        ->test(InspectorDashboard::class, ['user' => $inspector])
        ->assertSet('month', '')
        ->assertViewHas('selectedMonth', fn (CarbonImmutable $m) => $m->format('Y-m') === '2026-10')
        ->assertViewHas('canGoNext', false);
});

it('DSH-T01 navigates back with previousMonth and forward with nextMonth', function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
    $inspector = createLivewireInspector();

    $test = Livewire::test(InspectorDashboard::class, ['user' => $inspector]);

    // Initially at current month (2026-10)
    $test->assertSet('month', '')
        ->assertViewHas('canGoNext', false);

    // Call previousMonth -> moves to 2026-09
    $test->call('previousMonth')
        ->assertSet('month', '2026-09')
        ->assertViewHas('selectedMonth', fn (CarbonImmutable $m) => $m->format('Y-m') === '2026-09')
        ->assertViewHas('canGoNext', true);

    // Call previousMonth again -> moves to 2026-08
    $test->call('previousMonth')
        ->assertSet('month', '2026-08')
        ->assertViewHas('selectedMonth', fn (CarbonImmutable $m) => $m->format('Y-m') === '2026-08')
        ->assertViewHas('canGoNext', true);

    // Call nextMonth -> moves back to 2026-09
    $test->call('nextMonth')
        ->assertSet('month', '2026-09')
        ->assertViewHas('selectedMonth', fn (CarbonImmutable $m) => $m->format('Y-m') === '2026-09')
        ->assertViewHas('canGoNext', true);

    // Call nextMonth again -> moves back to current month (2026-10)
    $test->call('nextMonth')
        ->assertSet('month', '')
        ->assertViewHas('selectedMonth', fn (CarbonImmutable $m) => $m->format('Y-m') === '2026-10')
        ->assertViewHas('canGoNext', false);
});

it('DSH-T01 does not allow nextMonth to advance past the current month', function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
    $inspector = createLivewireInspector();

    $test = Livewire::test(InspectorDashboard::class, ['user' => $inspector]);

    // Initially at current month
    $test->assertSet('month', '')
        ->assertViewHas('canGoNext', false);

    // Attempting nextMonth on current month does nothing
    $test->call('nextMonth')
        ->assertSet('month', '')
        ->assertViewHas('selectedMonth', fn (CarbonImmutable $m) => $m->format('Y-m') === '2026-10')
        ->assertViewHas('canGoNext', false);
});

it('DSH-T07 query count is bounded and free of N+1 problems regardless of job volume', function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
    $inspector = createLivewireInspector();
    $seller = User::factory()->user()->create();
    $payer = User::factory()->user()->create();
    // Create 20 active jobs and 20 ledger entries (one payment each: unique per payment and entry type)
    for ($i = 1; $i <= 20; $i++) {
        $payment = Payment::factory()->for($payer, 'payer')->create();

        $listing = Listing::factory()->active()->for($seller, 'seller')->create([
            'title' => "Vehicle {$i}",
            'area_name' => "District {$i}",
        ]);

        InspectionJob::factory()->for($listing, 'listing')->for($inspector, 'inspector')->create([
            'status' => JobStatus::InProgress,
            'scheduled_at' => CarbonImmutable::parse('2026-10-10 10:00:00'),
        ]);

        LedgerEntry::factory()->for($payment, 'payment')->for($inspector, 'account')->create([
            'entry_type' => LedgerEntryType::InspectionFee,
            'amount' => 50_000,
            'created_at' => CarbonImmutable::parse('2026-10-10 12:00:00'),
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::test(InspectorDashboard::class, ['user' => $inspector])
        ->assertOk();

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Without eager loading, 20 jobs would produce > 30 queries.
    // Fixed aggregated/eager-loaded queries must remain below 20.
    expect($queryCount)->toBeLessThan(20);
});

it('DSH-T13 renders dashboard UI with inspector greeting, formatted earnings, change chip, and month picker', function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
    $inspector = createLivewireInspector('Budi Santoso');
    $payer = User::factory()->user()->create();

    // Previous month (2026-09) ledger entry with its own payment
    $paymentPrev = Payment::factory()->for($payer, 'payer')->create();
    LedgerEntry::factory()->for($paymentPrev, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 100_000,
        'created_at' => CarbonImmutable::parse('2026-09-10 12:00:00'),
    ]);

    // Current month (2026-10) ledger entry with its own payment
    $paymentCurr = Payment::factory()->for($payer, 'payer')->create();
    LedgerEntry::factory()->for($paymentCurr, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 150_000,
        'created_at' => CarbonImmutable::parse('2026-10-10 12:00:00'),
    ]);

    Livewire::test(InspectorDashboard::class, ['user' => $inspector])
        ->assertOk()
        ->assertSee('Halo, Budi Santoso')
        ->assertSee('Rp150.000')
        ->assertSee('+50%')
        ->assertSee('Okt 2026')
        ->assertSeeHtml('disabled');
});

it('DSH-T14 renders action buttons: Isi Hasil Inspeksi for in_progress and Lihat Pekerjaan for taken', function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
    $inspector = createLivewireInspector();
    $seller = User::factory()->user()->create();

    $listing1 = Listing::factory()->active()->for($seller, 'seller')->create(['title' => 'Toyota Avanza In Progress']);
    $listing2 = Listing::factory()->active()->for($seller, 'seller')->create(['title' => 'Honda Civic Taken']);

    InspectionJob::factory()->for($listing1, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::InProgress,
        'scheduled_at' => CarbonImmutable::parse('2026-10-16 10:00:00'),
    ]);

    InspectionJob::factory()->for($listing2, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::Taken,
        'scheduled_at' => null,
    ]);

    Livewire::test(InspectorDashboard::class, ['user' => $inspector])
        ->assertOk()
        ->assertSee('Toyota Avanza In Progress')
        ->assertSee('Honda Civic Taken')
        ->assertSee('Isi Hasil Inspeksi')
        ->assertSee('Lihat Pekerjaan')
        ->assertSee('Belum dijadwalkan');
});

it('DSH-T15 renders friendly empty state when no tasks are pending', function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
    $inspector = createLivewireInspector();

    Livewire::test(InspectorDashboard::class, ['user' => $inspector])
        ->assertOk()
        ->assertSee(__('dashboard.inspector.tasks_empty_title'))
        ->assertSee(route('jobs.open'));
});

it('DSH-T16 renders empty earnings text when month earnings are zero', function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
    $inspector = createLivewireInspector();

    Livewire::test(InspectorDashboard::class, ['user' => $inspector])
        ->assertOk()
        ->assertSee('Belum ada penghasilan bulan ini');
});

it('DSH-T17 does not leak private coordinates, full address, or chassis number in rendered HTML', function () {
    Carbon::setTestNow('2026-10-15 12:00:00');
    $inspector = createLivewireInspector();
    $seller = User::factory()->user()->create();

    $listing = Listing::factory()->active()->for($seller, 'seller')->create([
        'title' => 'Mitsubishi Xpander Private Test',
        'lat' => -6.2088000,
        'lng' => 106.8456000,
        'address_full' => 'Jl. Rahasia Boulevard No. 99, RT 01 RW 02',
        'chassis_number' => 'MHFXPANDERSECRET999',
    ]);

    InspectionJob::factory()->for($listing, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::InProgress,
    ]);

    $response = Livewire::test(InspectorDashboard::class, ['user' => $inspector]);

    $response->assertOk()
        ->assertSee('Mitsubishi Xpander Private Test')
        ->assertDontSee('-6.2088')
        ->assertDontSee('106.8456')
        ->assertDontSee('Jl. Rahasia Boulevard No. 99')
        ->assertDontSee('MHFXPANDERSECRET999');
});
