<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Tests\Unit;

use App\Domains\Dashboard\DTOs\EarningsSlice;
use App\Domains\Dashboard\DTOs\InspectorDashboardSummary;
use App\Domains\Dashboard\DTOs\InspectorTask;
use App\Domains\Dashboard\ReadModels\InspectorDashboardReader;
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

uses(RefreshDatabase::class);

function createTestInspector(string $name = 'Test Inspector', float $ratingAvg = 4.8, int $ratingCount = 12): User
{
    $inspector = User::factory()->create([
        'name' => $name,
        'role' => UserRole::Inspector,
        'status' => UserStatus::Active,
    ]);

    InspectorProfile::factory()->for($inspector, 'user')->create([
        'verification_status' => VerificationStatus::Approved,
        'rating_avg' => $ratingAvg,
        'rating_count' => $ratingCount,
        'vehicle_types' => ['car', 'motorcycle'],
    ]);

    return $inspector;
}

it('DSH-T03 summary calculates current vs previous month earnings and change percent', function () {
    $reader = new InspectorDashboardReader;
    $inspector = createTestInspector();
    $payer = User::factory()->user()->create();
    $payment = Payment::factory()->for($payer, 'payer')->create();

    $currentMonth = CarbonImmutable::parse('2026-10-15');

    // Current month entries (total: 150_000)
    LedgerEntry::factory()->for($payment, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 100_000,
        'created_at' => CarbonImmutable::parse('2026-10-05 10:00:00'),
    ]);
    LedgerEntry::factory()->for($payment, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::AccessFee,
        'amount' => 50_000,
        'created_at' => CarbonImmutable::parse('2026-10-20 12:00:00'),
    ]);

    // Previous month entries (total: 100_000)
    LedgerEntry::factory()->for($payment, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 100_000,
        'created_at' => CarbonImmutable::parse('2026-09-15 14:00:00'),
    ]);

    $summary = $reader->summary($inspector, $currentMonth);

    expect($summary)->toBeInstanceOf(InspectorDashboardSummary::class)
        ->and($summary->netEarnings)->toBe(150_000)
        ->and($summary->previousMonthEarnings)->toBe(100_000)
        ->and($summary->changePercent)->toBe(50.0);
});

it('DSH-T03 summary returns null changePercent when previous month earnings is zero', function () {
    $reader = new InspectorDashboardReader;
    $inspector = createTestInspector();
    $payer = User::factory()->user()->create();
    $payment = Payment::factory()->for($payer, 'payer')->create();

    $currentMonth = CarbonImmutable::parse('2026-10-15');

    LedgerEntry::factory()->for($payment, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 75_000,
        'created_at' => CarbonImmutable::parse('2026-10-10 10:00:00'),
    ]);

    $summary = $reader->summary($inspector, $currentMonth);

    expect($summary->netEarnings)->toBe(75_000)
        ->and($summary->previousMonthEarnings)->toBe(0)
        ->and($summary->changePercent)->toBeNull();
});

it('DSH-T02 summary isolates inspector data and excludes other inspectors', function () {
    $reader = new InspectorDashboardReader;
    $inspectorA = createTestInspector('Inspector A', 4.5, 10);
    $inspectorB = createTestInspector('Inspector B', 3.2, 4);

    $payer = User::factory()->user()->create();
    $payment = Payment::factory()->for($payer, 'payer')->create();
    $month = CarbonImmutable::parse('2026-10-15');

    // Inspector A ledger entry
    LedgerEntry::factory()->for($payment, 'payment')->for($inspectorA, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 50_000,
        'created_at' => CarbonImmutable::parse('2026-10-10'),
    ]);

    // Inspector B ledger entry
    LedgerEntry::factory()->for($payment, 'payment')->for($inspectorB, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 120_000,
        'created_at' => CarbonImmutable::parse('2026-10-10'),
    ]);

    // Inspector A job
    $listingA = Listing::factory()->active()->create();
    InspectionJob::factory()->for($listingA, 'listing')->for($inspectorA, 'inspector')->create([
        'status' => JobStatus::InProgress,
    ]);

    // Inspector B job
    $listingB = Listing::factory()->active()->create();
    InspectionJob::factory()->for($listingB, 'listing')->for($inspectorB, 'inspector')->create([
        'status' => JobStatus::InProgress,
    ]);

    $summaryA = $reader->summary($inspectorA, $month);
    $summaryB = $reader->summary($inspectorB, $month);

    expect($summaryA->netEarnings)->toBe(50_000)
        ->and($summaryA->activeJobs)->toBe(1)
        ->and($summaryA->ratingAvg)->toBe(4.5)
        ->and($summaryA->ratingCount)->toBe(10)
        ->and($summaryB->netEarnings)->toBe(120_000)
        ->and($summaryB->activeJobs)->toBe(1)
        ->and($summaryB->ratingAvg)->toBe(3.2)
        ->and($summaryB->ratingCount)->toBe(4);
});

it('DSH-T03 summary correctly respects month boundaries at start and end', function () {
    $reader = new InspectorDashboardReader;
    $inspector = createTestInspector();
    $payer = User::factory()->user()->create();
    $payment = Payment::factory()->for($payer, 'payer')->create();

    $month = CarbonImmutable::parse('2026-10-15');

    // Exact start of month: 2026-10-01 00:00:00 -> INCLUDED
    LedgerEntry::factory()->for($payment, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 10_000,
        'created_at' => CarbonImmutable::parse('2026-10-01 00:00:00'),
    ]);

    // Exact end of month: 2026-10-31 23:59:59 -> INCLUDED
    LedgerEntry::factory()->for($payment, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 20_000,
        'created_at' => CarbonImmutable::parse('2026-10-31 23:59:59'),
    ]);

    // One second before start of month: 2026-09-30 23:59:59 -> PREVIOUS MONTH
    LedgerEntry::factory()->for($payment, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 40_000,
        'created_at' => CarbonImmutable::parse('2026-09-30 23:59:59'),
    ]);

    // One second after end of month: 2026-11-01 00:00:00 -> FUTURE MONTH (NOT INCLUDED)
    LedgerEntry::factory()->for($payment, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 80_000,
        'created_at' => CarbonImmutable::parse('2026-11-01 00:00:00'),
    ]);

    $summary = $reader->summary($inspector, $month);

    expect($summary->netEarnings)->toBe(30_000)
        ->and($summary->previousMonthEarnings)->toBe(40_000);
});

it('DSH-T01 summary counts available, active, and completed jobs', function () {
    $reader = new InspectorDashboardReader;
    $inspector = createTestInspector();
    $seller = User::factory()->user()->create();

    // Available jobs: paid without inspector_id
    $l1 = Listing::factory()->active()->for($seller, 'seller')->create();
    $l2 = Listing::factory()->active()->for($seller, 'seller')->create();
    InspectionJob::factory()->for($l1, 'listing')->paid()->create(['inspector_id' => null]);
    InspectionJob::factory()->for($l2, 'listing')->paid()->create(['inspector_id' => null]);

    // Active jobs for inspector: taken, scheduled, in_progress, result_submitted
    $l3 = Listing::factory()->active()->for($seller, 'seller')->create();
    $l4 = Listing::factory()->active()->for($seller, 'seller')->create();
    $l5 = Listing::factory()->active()->for($seller, 'seller')->create();
    $l6 = Listing::factory()->active()->for($seller, 'seller')->create();
    InspectionJob::factory()->for($l3, 'listing')->for($inspector, 'inspector')->create(['status' => JobStatus::Taken]);
    InspectionJob::factory()->for($l4, 'listing')->for($inspector, 'inspector')->create(['status' => JobStatus::Scheduled]);
    InspectionJob::factory()->for($l5, 'listing')->for($inspector, 'inspector')->create(['status' => JobStatus::InProgress]);
    InspectionJob::factory()->for($l6, 'listing')->for($inspector, 'inspector')->create(['status' => JobStatus::ResultSubmitted]);

    // Completed jobs: 1 in month, 1 in previous month
    $month = CarbonImmutable::parse('2026-10-15');
    $l7 = Listing::factory()->active()->for($seller, 'seller')->create();
    $l8 = Listing::factory()->active()->for($seller, 'seller')->create();
    InspectionJob::factory()->for($l7, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::Completed,
        'completed_at' => CarbonImmutable::parse('2026-10-10 12:00:00'),
    ]);
    InspectionJob::factory()->for($l8, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::Completed,
        'completed_at' => CarbonImmutable::parse('2026-09-20 12:00:00'),
    ]);

    $summary = $reader->summary($inspector, $month);

    expect($summary->availableJobs)->toBe(2)
        ->and($summary->activeJobs)->toBe(4)
        ->and($summary->completedInMonth)->toBe(1)
        ->and($summary->totalCompleted)->toBe(2);
});

it('DSH-T01 tasks orders closest scheduled first, unscheduled last, then by id', function () {
    $reader = new InspectorDashboardReader;
    $inspector = createTestInspector();
    $seller = User::factory()->user()->create();

    $l1 = Listing::factory()->active()->for($seller, 'seller')->create(['title' => 'Car Oct 10', 'area_name' => 'Sukajadi']);
    $l2 = Listing::factory()->active()->for($seller, 'seller')->create(['title' => 'Car Oct 20', 'area_name' => 'Tampan']);
    $l3 = Listing::factory()->active()->for($seller, 'seller')->create(['title' => 'Car No Schedule 1', 'area_name' => 'Marpoyan']);
    $l4 = Listing::factory()->active()->for($seller, 'seller')->create(['title' => 'Car No Schedule 2', 'area_name' => 'Senapelan']);

    // Job with scheduled_at on Oct 20
    $jobLate = InspectionJob::factory()->for($l2, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::Scheduled,
        'scheduled_at' => CarbonImmutable::parse('2026-10-20 10:00:00'),
    ]);

    // Job with scheduled_at on Oct 10 (earlier)
    $jobEarly = InspectionJob::factory()->for($l1, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::Scheduled,
        'scheduled_at' => CarbonImmutable::parse('2026-10-10 10:00:00'),
    ]);

    // Jobs without schedule (null scheduled_at)
    $jobNoSched1 = InspectionJob::factory()->for($l3, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::Taken,
        'scheduled_at' => null,
    ]);
    $jobNoSched2 = InspectionJob::factory()->for($l4, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::Taken,
        'scheduled_at' => null,
    ]);

    $tasks = $reader->tasks($inspector);

    expect($tasks)->toHaveCount(4)
        ->and($tasks[0]->jobId)->toBe((string) $jobEarly->id)
        ->and($tasks[0]->listingTitle)->toBe('Car Oct 10')
        ->and($tasks[0]->city)->toBe('Sukajadi')
        ->and($tasks[0]->scheduledAt?->format('Y-m-d'))->toBe('2026-10-10')
        ->and($tasks[1]->jobId)->toBe((string) $jobLate->id)
        ->and($tasks[1]->scheduledAt?->format('Y-m-d'))->toBe('2026-10-20');

    // Unscheduled tasks are at the end, ordered by id
    $expectedUnscheduled = [$jobNoSched1->id, $jobNoSched2->id];
    sort($expectedUnscheduled);

    expect($tasks[2]->scheduledAt)->toBeNull()
        ->and($tasks[3]->scheduledAt)->toBeNull()
        ->and([$tasks[2]->jobId, $tasks[3]->jobId])->toBe($expectedUnscheduled);
});

it('DSH-T01 tasks assigns result.fill for in_progress and jobs.show for taken/scheduled', function () {
    $reader = new InspectorDashboardReader;
    $inspector = createTestInspector();
    $seller = User::factory()->user()->create();

    $l1 = Listing::factory()->active()->for($seller, 'seller')->create(['title' => 'Car In Progress']);
    $l2 = Listing::factory()->active()->for($seller, 'seller')->create(['title' => 'Car Scheduled']);
    $l3 = Listing::factory()->active()->for($seller, 'seller')->create(['title' => 'Car Taken']);

    $jobProgress = InspectionJob::factory()->for($l1, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::InProgress,
        'scheduled_at' => CarbonImmutable::parse('2026-10-01 10:00:00'),
    ]);
    $jobScheduled = InspectionJob::factory()->for($l2, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::Scheduled,
        'scheduled_at' => CarbonImmutable::parse('2026-10-02 10:00:00'),
    ]);
    $jobTaken = InspectionJob::factory()->for($l3, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::Taken,
        'scheduled_at' => CarbonImmutable::parse('2026-10-03 10:00:00'),
    ]);

    $tasks = $reader->tasks($inspector);

    expect($tasks)->toHaveCount(3)
        ->and($tasks[0]->actionRoute)->toBe('result.fill')
        ->and($tasks[0]->actionParams)->toBe(['job' => (string) $jobProgress->id])
        ->and($tasks[1]->actionRoute)->toBe('jobs.show')
        ->and($tasks[1]->actionParams)->toBe(['job' => (string) $jobScheduled->id])
        ->and($tasks[2]->actionRoute)->toBe('jobs.show')
        ->and($tasks[2]->actionParams)->toBe(['job' => (string) $jobTaken->id]);
});

it('DSH-T09 tasks preserves data privacy and does not expose exact coordinates or address', function () {
    $reader = new InspectorDashboardReader;
    $inspector = createTestInspector();
    $seller = User::factory()->user()->create();

    $listing = Listing::factory()->active()->for($seller, 'seller')->create([
        'title' => 'Private Car',
        'area_name' => 'Payung Sekaki',
        'address_full' => 'Jl. Anggrek No. 123 RT 01 RW 02',
        'lat' => 0.5123456,
        'lng' => 101.4456789,
    ]);

    InspectionJob::factory()->for($listing, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::Taken,
    ]);

    $tasks = $reader->tasks($inspector);

    expect($tasks)->toHaveCount(1)
        ->and($tasks[0]->city)->toBe('Payung Sekaki')
        ->and($tasks[0]->listingTitle)->toBe('Private Car');

    // Verify InspectorTask class structure has no private properties
    $task = $tasks[0];
    $taskReflection = new ReflectionClass($task);

    expect($taskReflection->hasProperty('lat'))->toBeFalse()
        ->and($taskReflection->hasProperty('lng'))->toBeFalse()
        ->and($taskReflection->hasProperty('address_full'))->toBeFalse()
        ->and($taskReflection->hasProperty('chassis_number'))->toBeFalse();
});

it('DSH-T01 tasks respects limit parameter', function () {
    $reader = new InspectorDashboardReader;
    $inspector = createTestInspector();
    $seller = User::factory()->user()->create();

    for ($i = 1; $i <= 5; $i++) {
        $listing = Listing::factory()->active()->for($seller, 'seller')->create();
        InspectionJob::factory()->for($listing, 'listing')->for($inspector, 'inspector')->create([
            'status' => JobStatus::Taken,
        ]);
    }

    $tasks = $reader->tasks($inspector, limit: 3);

    expect($tasks)->toHaveCount(3);
});

it('DSH-T03 earningsBreakdown returns empty array when total is zero', function () {
    $reader = new InspectorDashboardReader;
    $inspector = createTestInspector();
    $month = CarbonImmutable::parse('2026-10-15');

    $breakdown = $reader->earningsBreakdown($inspector, $month);

    expect($breakdown)->toBeArray()->toBeEmpty();
});

it('DSH-T03 earningsBreakdown calculates slice percentages correctly', function () {
    $reader = new InspectorDashboardReader;
    $inspector = createTestInspector();
    $payer = User::factory()->user()->create();
    $payment = Payment::factory()->for($payer, 'payer')->create();
    $month = CarbonImmutable::parse('2026-10-15');

    // 80,000 InspectionFee (80%) and 20,000 AccessFee (20%) -> Total 100,000
    LedgerEntry::factory()->for($payment, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 80_000,
        'created_at' => CarbonImmutable::parse('2026-10-05'),
    ]);
    LedgerEntry::factory()->for($payment, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::AccessFee,
        'amount' => 20_000,
        'created_at' => CarbonImmutable::parse('2026-10-12'),
    ]);

    $breakdown = $reader->earningsBreakdown($inspector, $month);

    expect($breakdown)->toHaveCount(2)
        ->and($breakdown[0])->toBeInstanceOf(EarningsSlice::class)
        ->and($breakdown[0]->type)->toBe(LedgerEntryType::InspectionFee)
        ->and($breakdown[0]->amount)->toBe(80_000)
        ->and($breakdown[0]->percent)->toBe(80.0)
        ->and($breakdown[1]->type)->toBe(LedgerEntryType::AccessFee)
        ->and($breakdown[1]->amount)->toBe(20_000)
        ->and($breakdown[1]->percent)->toBe(20.0);
});

it('DSH-T10 monthlySeries returns exactly 6 elements with zero-filled empty months', function () {
    $reader = new InspectorDashboardReader;
    $inspector = createTestInspector();
    $payer = User::factory()->user()->create();
    $payment = Payment::factory()->for($payer, 'payer')->create();
    $seller = User::factory()->user()->create();

    $endMonth = CarbonImmutable::parse('2026-10-15');

    // Create completed job in 2026-10
    $listing = Listing::factory()->active()->for($seller, 'seller')->create();
    InspectionJob::factory()->for($listing, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::Completed,
        'completed_at' => CarbonImmutable::parse('2026-10-08 10:00:00'),
    ]);

    // Create ledger entry in 2026-10 (45_000)
    LedgerEntry::factory()->for($payment, 'payment')->for($inspector, 'account')->create([
        'entry_type' => LedgerEntryType::InspectionFee,
        'amount' => 45_000,
        'created_at' => CarbonImmutable::parse('2026-10-08 10:00:00'),
    ]);

    // Create completed job in 2026-08 (2 months before)
    InspectionJob::factory()->for($listing, 'listing')->for($inspector, 'inspector')->create([
        'status' => JobStatus::Completed,
        'completed_at' => CarbonImmutable::parse('2026-08-14 10:00:00'),
    ]);

    $series = $reader->monthlySeries($inspector, $endMonth);

    expect($series)->toBeArray()->toHaveCount(6);

    // Verify chronological order ending at 2026-10
    $expectedYms = ['2026-05', '2026-06', '2026-07', '2026-08', '2026-09', '2026-10'];
    expect(array_column($series, 'ym'))->toBe($expectedYms);

    // Month with data (2026-10)
    $oct = $series[5];
    expect($oct['ym'])->toBe('2026-10')
        ->and($oct['completed'])->toBe(1)
        ->and($oct['earnings'])->toBe(45_000)
        ->and($oct['label'])->toBe('Okt');

    // Month with job only (2026-08)
    $aug = $series[3];
    expect($aug['ym'])->toBe('2026-08')
        ->and($aug['completed'])->toBe(1)
        ->and($aug['earnings'])->toBe(0);

    // Empty month (2026-05)
    $may = $series[0];
    expect($may['ym'])->toBe('2026-05')
        ->and($may['completed'])->toBe(0)
        ->and($may['earnings'])->toBe(0)
        ->and($may['label'])->toBe('Mei');
});
