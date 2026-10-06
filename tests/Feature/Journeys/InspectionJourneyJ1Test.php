<?php

declare(strict_types=1);

namespace Tests\Feature\Journeys;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\BrowseOpenJobs;
use App\Domains\Inspection\Actions\CompleteJob;
use App\Domains\Inspection\Actions\RequestInspection;
use App\Domains\Inspection\Actions\ScheduleJob;
use App\Domains\Inspection\Actions\StartInspection;
use App\Domains\Inspection\Actions\TakeJob;
use App\Domains\Inspection\DTOs\OpenJobFilter;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Inspection\Events\JobPaid;
use App\Domains\Inspection\Events\JobScheduled;
use App\Domains\Inspection\Events\JobStarted;
use App\Domains\Inspection\Events\JobTaken;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Actions\ApproveListing;
use App\Domains\Listing\Actions\CreateListing;
use App\Domains\Listing\DTOs\CreateListingDTO;
use App\Domains\Listing\Enums\FuelType;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\Transmission;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Events\ListingApproved;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Result\Actions\GetOrCreateDraftResult;
use App\Domains\Result\Actions\SaveDraftResult;
use App\Domains\Result\Actions\SubmitInspectionResult;
use App\Domains\Result\Actions\UploadResultPhoto;
use App\Domains\Result\DTOs\UploadPhotoData;
use App\Domains\Result\Enums\InspectionRecommendation;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Events\ResultSubmitted;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\TemplateItem;
use App\Domains\Setting\Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\ListingPrivacyHelper;

uses(RefreshDatabase::class);

it('executes full end-to-end inspection journey J1 from listing to inspected badge', function () {
    // 0. Pengaturan lingkungan & pelacak event nyata (invarian)
    Storage::fake('private');
    Storage::fake('public');
    (new SettingSeeder)->run();

    $dispatchedEvents = [];
    Event::listen([
        ListingApproved::class,
        PaymentSucceeded::class,
        JobPaid::class,
        JobTaken::class,
        JobScheduled::class,
        JobStarted::class,
        ResultSubmitted::class,
        JobCompleted::class,
    ], function ($event) use (&$dispatchedEvents): void {
        $name = get_class($event);
        $dispatchedEvents[$name] = ($dispatchedEvents[$name] ?? 0) + 1;
    });

    // Buat template inspeksi mobil yang aktif dan terstruktur
    $template = InspectionTemplate::factory()->car()->create([
        'name' => 'Template Inspeksi Mobil v1',
        'version' => 1,
        'is_active' => true,
    ]);

    $itemPhoto = TemplateItem::factory()->create([
        'template_id' => $template->id,
        'category' => 'Mesin',
        'label' => 'Kondisi Mesin',
        'weight' => 1,
        'is_required' => true,
        'requires_photo' => true,
        'is_critical' => false,
        'sort_order' => 1,
    ]);

    $itemChecklist = TemplateItem::factory()->create([
        'template_id' => $template->id,
        'category' => 'Dokumen',
        'label' => 'Kelengkapan STNK dan BPKB',
        'weight' => 1,
        'is_required' => true,
        'requires_photo' => false,
        'is_critical' => false,
        'sort_order' => 2,
    ]);

    // 1. Penjual (role user) membuat listing lewat Action CreateListing
    $seller = User::factory()->user()->create([
        'name' => 'Budi Penjual',
        'email' => 'budi.penjual@example.com',
        'status' => UserStatus::Active,
    ]);

    $photos = [
        UploadedFile::fake()->image('avanza-depan.jpg', 800, 600)->size(50),
        UploadedFile::fake()->image('avanza-samping.jpg', 800, 600)->size(50),
        UploadedFile::fake()->image('avanza-belakang.jpg', 800, 600)->size(50),
    ];

    $slots = [
        [
            'slot_start' => now()->addDays(2)->setTime(10, 0)->toIso8601String(),
            'note' => 'Bisa cek pagi di rumah',
        ],
    ];

    $createDto = new CreateListingDTO(
        vehicle_type: VehicleType::Car,
        title: 'Toyota Avanza 1.3 G Manual 2018',
        brand: 'Toyota',
        model: 'Avanza',
        year: 2018,
        price: 135_000_000,
        negotiable: true,
        odometer_km: 62000,
        transmission: Transmission::Manual,
        fuel: FuelType::Petrol,
        color: 'Hitam Metalik',
        inspection_place: 'Jl. HR Soebrantas No. 10, Tampan, Pekanbaru',
        photos: $photos,
        slots: $slots,
        chassis_number: 'MHKA1234567890123',
        description: 'Mobil pakaian pribadi, tangan pertama, mesin halus siap pakai.',
        area_name: 'Tampan',
        lat: 0.5071,
        lng: 101.4478,
        address_full: 'Jl. HR Soebrantas No. 10, Tampan, Pekanbaru',
        cover_photo_index: 0,
    );

    /** @var Listing $listing */
    $listing = app(CreateListing::class)->handle($seller, $createDto);

    expect($listing->status)->toBe(ListingStatus::PendingReview)
        ->and($listing->seller_id)->toBe($seller->id);

    // Buka rute penjual: Listing Saya
    $responseSellerListings = $this->actingAs($seller)->get(route('listings.mine'));
    $responseSellerListings->assertOk()->assertSee('Toyota Avanza 1.3 G Manual 2018');

    // 2. Admin menyetujui lewat Action ApproveListing, status active
    $admin = User::factory()->admin()->create([
        'name' => 'Admin Simokas',
        'email' => 'admin@example.com',
        'status' => UserStatus::Active,
    ]);

    app(ApproveListing::class)->handle($admin, $listing);

    $freshListing = $listing->fresh();
    expect($freshListing->status)->toBe(ListingStatus::Active)
        ->and($freshListing->validated_by)->toBe($admin->id);

    // 3. Pembeli (role user lain) membuka katalog dan detail listing (GET rute publik, tanpa data privat)
    $buyer = User::factory()->user()->create([
        'name' => 'Andi Pembeli',
        'email' => 'andi.pembeli@example.com',
        'status' => UserStatus::Active,
    ]);

    $catalogResponse = $this->actingAs($buyer)->get(route('catalog.index'));
    $catalogResponse->assertOk()
        ->assertSee('Toyota Avanza 1.3 G Manual 2018');
    ListingPrivacyHelper::assertNoPrivateListingData($catalogResponse, [
        $listing->address_full,
        'MHKA1234567890123',
    ]);

    $detailResponse = $this->actingAs($buyer)->get(route('catalog.show', ['listing' => $listing->id]));
    $detailResponse->assertOk()
        ->assertSee('Toyota Avanza 1.3 G Manual 2018')
        ->assertSee(__('inspection.request.submit'));
    ListingPrivacyHelper::assertNoPrivateListingData($detailResponse, [
        $listing->address_full,
        'MHKA1234567890123',
    ]);

    // 4. Pembeli mengajukan inspeksi lewat Action RequestInspection
    $requestResult = app(RequestInspection::class)->handle($buyer, $listing);
    /** @var InspectionJob $job */
    $job = $requestResult['job'];
    $bill = $requestResult['bill'];

    expect($job->status)->toBe(JobStatus::Requested)
        ->and($job->fee_amount)->toBe(50000)
        ->and($job->platform_fee_pct)->toBe(10)
        ->and($bill->amount)->toBe(50000);

    // Buka halaman tagihan simulasi
    $paymentPageResponse = $this->actingAs($buyer)->get(route('payments.show', ['payment' => $bill->id]));
    $paymentPageResponse->assertOk();

    // Buka halaman Inspeksi Saya milik pembeli
    $myInspectionsResponse = $this->actingAs($buyer)->get(route('jobs.mine'));
    $myInspectionsResponse->assertOk()
        ->assertSee(__('inspection.mine.title'));

    // Bayar lewat jalur resmi simulasi HTTP (POST payments.simulate)
    $paymentSimulationResponse = $this->actingAs($buyer)->postJson(route('payments.simulate'), [
        'payment_id' => $bill->id,
        'method' => 'qris',
        'outcome' => 'success',
    ]);
    $paymentSimulationResponse->assertOk();

    // Verifikasi reaksi event: job paid & listing in_inspection
    expect($job->fresh()->status)->toBe(JobStatus::Paid)
        ->and($job->fresh()->available_since)->not->toBeNull()
        ->and($listing->fresh()->status)->toBe(ListingStatus::InInspection);

    // 5. Inspektor (approved, keahlian cocok) melihat job di daftar terbuka, mengambil, menjadwalkan, memulai
    $inspector = User::factory()->inspector()->create([
        'name' => 'Doni Inspektor',
        'email' => 'doni.inspector@example.com',
        'status' => UserStatus::Active,
    ]);

    InspectorProfile::factory()->create([
        'user_id' => $inspector->id,
        'verification_status' => VerificationStatus::Approved,
        'vehicle_types' => ['car', 'motorcycle'],
    ]);

    // Buka halaman web Job Terbuka
    $openJobsPageResponse = $this->actingAs($inspector)->get(route('jobs.open'));
    $openJobsPageResponse->assertOk()
        ->assertSee(__('inspection.open_jobs.title'));

    // Lihat lewat Action BrowseOpenJobs
    $openJobs = app(BrowseOpenJobs::class)->handle($inspector, new OpenJobFilter);
    expect($openJobs->total())->toBeGreaterThanOrEqual(1);

    // Ambil job lewat Action TakeJob
    app(TakeJob::class)->handle($inspector, $job->fresh());
    expect($job->fresh()->status)->toBe(JobStatus::Taken)
        ->and($job->fresh()->inspector_id)->toBe($inspector->id);

    // Buka halaman Detail Job (sekarang memuat detail lengkap untuk inspektor yang ditugaskan)
    $jobDetailResponse = $this->actingAs($inspector)->get(route('jobs.show', ['job' => $job->id]));
    $jobDetailResponse->assertOk()
        ->assertSee(__('inspection.detail.title'));

    // Jadwalkan inspeksi lewat ScheduleJob (waktu masa depan)
    $scheduledAt = now()->addDay()->setTime(9, 30);
    app(ScheduleJob::class)->handle($inspector, $job->fresh(), $scheduledAt);
    expect($job->fresh()->status)->toBe(JobStatus::Scheduled);

    // Majukan waktu ke jadwal inspeksi dan mulai inspeksi lewat StartInspection
    Carbon::setTestNow($scheduledAt);
    app(StartInspection::class)->handle($inspector, $job->fresh());
    expect($job->fresh()->status)->toBe(JobStatus::InProgress)
        ->and($job->fresh()->started_at)->not->toBeNull();

    // 6. Inspektor mengisi draf hasil, mengunggah foto kamera, dan mengirim hasil
    // Buka formulir inspeksi
    $fillFormResponse = $this->actingAs($inspector)->get(route('result.fill', ['job' => $job->id]));
    $fillFormResponse->assertOk()
        ->assertSee(__('inspection.form.title'));

    $draft = app(GetOrCreateDraftResult::class)->handle($inspector, $job->fresh());
    expect($draft->status)->toBe(ResultStatus::Draft);

    // Unggah foto wajib
    $photoFile = UploadedFile::fake()->image('mesin-avanza.jpg', 800, 600)->size(80);
    $uploadData = new UploadPhotoData(
        file: $photoFile,
        templateItemId: (string) $itemPhoto->id,
        stampedAt: now(),
        isCameraCapture: true,
    );
    $uploadedPhoto = app(UploadResultPhoto::class)->handle($inspector, $draft, $uploadData);
    expect($uploadedPhoto->is_camera_capture)->toBeTrue();

    // Simpan semua item wajib
    $itemsPayload = [
        (string) $itemPhoto->id => [
            'rating' => ItemRating::Good->value,
            'note' => 'Blok mesin bersih, oli bening, tidak ada kebocoran.',
        ],
        (string) $itemChecklist->id => [
            'rating' => ItemRating::Good->value,
            'note' => 'STNK dan BPKB asli dan sesuai nomor mesin.',
        ],
    ];
    app(SaveDraftResult::class)->handle($inspector, $draft, $itemsPayload, 'Kendaraan terawat dengan sangat baik.');

    // Kirim hasil inspeksi
    $submittedResult = app(SubmitInspectionResult::class)->handle($inspector, $draft->fresh());
    expect($submittedResult->status)->toBe(ResultStatus::Submitted)
        ->and((float) $submittedResult->total_score)->toBe(100.0)
        ->and($submittedResult->recommendation)->toBe(InspectionRecommendation::Recommended)
        ->and($job->fresh()->status)->toBe(JobStatus::ResultSubmitted);

    // 7. Pemohon menerima hasil (CompleteJob lewat HTTP POST jobs.complete)
    $completeResponse = $this->actingAs($buyer)->post(route('jobs.complete', ['job' => $job->id]));
    $completeResponse->assertOk();

    // Job completed
    expect($job->fresh()->status)->toBe(JobStatus::Completed);

    // Fee inspektor diposting di ledger
    /** @var Ledger $ledger */
    $ledger = app(Ledger::class);
    expect($ledger->balanceFor((string) $inspector->id))->toBe(45000);

    // Entri ledger: platform_fee (5.000) + inspection_fee (45.000) = 50.000
    $jobLedgerEntries = LedgerEntry::query()->where('payment_id', $bill->id)->get();
    expect($jobLedgerEntries)->toHaveCount(2)
        ->and($jobLedgerEntries->sum('amount'))->toBe(50000);

    // Listing active dengan is_inspected true dan last_inspected_at terisi (lencana muncul)
    $finalListing = $listing->fresh();
    expect($finalListing->status)->toBe(ListingStatus::Active)
        ->and($finalListing->is_inspected)->toBeTrue()
        ->and($finalListing->last_inspected_at)->not->toBeNull();

    // Halaman detail listing publik menampilkan lencana Sudah Diinspeksi
    $inspectedListingPage = $this->actingAs($buyer)->get(route('catalog.show', ['listing' => $listing->id]));
    $inspectedListingPage->assertOk()
        ->assertSee(__('ui.badges.inspected'));

    // Halaman Inspeksi Saya menunjukkan status selesai
    $finalMyInspections = $this->actingAs($buyer)->get(route('jobs.mine'));
    $finalMyInspections->assertOk()
        ->assertSee(__('inspection.mine.title'));

    // 9. Assert invarian akhir:
    // Tepat satu pembayaran untuk job ini
    expect(Payment::query()->where('purpose', PaymentPurpose::Inspection)->where('ref_id', $job->id)->count())->toBe(1);

    // Tidak ada baris ledger selain dua entri (platform_fee dan inspection_fee)
    expect(LedgerEntry::query()->count())->toBe(2)
        ->and(LedgerEntry::query()->where('entry_type', LedgerEntryType::InspectionFee->value)->count())->toBe(1)
        ->and(LedgerEntry::query()->where('entry_type', LedgerEntryType::PlatformFee->value)->count())->toBe(1);

    // Jumlah event penting masing-masing tepat satu kali
    expect($dispatchedEvents[ListingApproved::class] ?? 0)->toBe(1)
        ->and($dispatchedEvents[PaymentSucceeded::class] ?? 0)->toBe(1)
        ->and($dispatchedEvents[JobPaid::class] ?? 0)->toBe(1)
        ->and($dispatchedEvents[JobTaken::class] ?? 0)->toBe(1)
        ->and($dispatchedEvents[JobScheduled::class] ?? 0)->toBe(1)
        ->and($dispatchedEvents[JobStarted::class] ?? 0)->toBe(1)
        ->and($dispatchedEvents[ResultSubmitted::class] ?? 0)->toBe(1)
        ->and($dispatchedEvents[JobCompleted::class] ?? 0)->toBe(1);

    Carbon::setTestNow();
});
