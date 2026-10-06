<?php

declare(strict_types=1);

namespace Tests\Feature\Journeys\Concerns;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Result\Enums\InspectionRecommendation;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HasCompletedInspectionSetup
{
    /**
     * @return array{
     *     seller: User,
     *     requester: User,
     *     inspector: User,
     *     listing: Listing,
     *     job: InspectionJob,
     *     payment: Payment,
     *     template: InspectionTemplate,
     *     templateItem: TemplateItem,
     *     result: InspectionResult,
     *     resultItem: ResultItem,
     *     photo: ResultPhoto,
     * }
     */
    protected function setupCompletedInspectionState(): array
    {
        Storage::fake('private');
        Storage::fake('public');

        $seller = User::factory()->user()->create([
            'name' => 'Penjual Amanah',
            'email' => 'seller-j2@example.com',
            'status' => UserStatus::Active,
        ]);

        $requester = User::factory()->user()->create([
            'name' => 'Pembeli Pertama',
            'email' => 'requester-j2@example.com',
            'status' => UserStatus::Active,
        ]);

        $inspector = User::factory()->inspector()->create([
            'name' => 'Inspektor Handal',
            'email' => 'inspector-j2@example.com',
            'status' => UserStatus::Active,
        ]);

        InspectorProfile::factory()->create([
            'user_id' => $inspector->id,
            'verification_status' => VerificationStatus::Approved,
            'vehicle_types' => ['car', 'motorcycle'],
        ]);

        $listing = Listing::factory()->active()->create([
            'seller_id' => $seller->id,
            'vehicle_type' => VehicleType::Car,
            'title' => 'Toyota Avanza 1.3 G Manual 2018',
            'brand' => 'Toyota',
            'model' => 'Avanza',
            'year' => 2018,
            'price' => 135_000_000,
            'is_inspected' => true,
            'last_inspected_at' => now(),
            'lat' => '0.5071000',
            'lng' => '101.4478000',
            'address_full' => 'Jl. Tuanku Tambusai No. 12',
            'chassis_number' => 'MHKA1234567890123',
        ]);

        $job = InspectionJob::factory()->create([
            'listing_id' => $listing->id,
            'requester_id' => $requester->id,
            'inspector_id' => $inspector->id,
            'status' => JobStatus::Completed,
            'fee_amount' => 50000,
            'platform_fee_pct' => 10,
            'available_since' => now()->subDays(2),
            'taken_at' => now()->subDays(2),
            'scheduled_at' => now()->subDay(),
            'started_at' => now()->subDay(),
            'submitted_at' => now()->subHours(6),
            'completed_at' => now()->subHours(2),
        ]);

        $payment = Payment::factory()->create([
            'payer_id' => $requester->id,
            'purpose' => PaymentPurpose::Inspection,
            'ref_id' => $job->id,
            'amount' => 50000,
            'status' => PaymentStatus::Success,
            'method' => PaymentMethod::Qris,
            'paid_at' => now()->subDays(2),
        ]);

        LedgerEntry::create([
            'payment_id' => $payment->id,
            'entry_type' => LedgerEntryType::PlatformFee->value,
            'account_user_id' => null,
            'amount' => 5000,
            'currency' => 'IDR',
            'notes' => 'Komisi platform inspeksi',
        ]);

        LedgerEntry::create([
            'payment_id' => $payment->id,
            'entry_type' => LedgerEntryType::InspectionFee->value,
            'account_user_id' => $inspector->id,
            'amount' => 45000,
            'currency' => 'IDR',
            'notes' => 'Payout inspeksi mobil',
        ]);

        $template = InspectionTemplate::factory()->car()->create([
            'name' => 'Template Inspeksi Mobil v1',
            'version' => 1,
            'is_active' => true,
        ]);

        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'category' => 'Mesin',
            'label' => 'Kondisi Mesin',
            'weight' => 1,
            'is_required' => true,
            'requires_photo' => true,
            'is_critical' => false,
            'sort_order' => 1,
        ]);

        $result = InspectionResult::factory()->create([
            'job_id' => $job->id,
            'listing_id' => $listing->id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Submitted,
            'total_score' => 100.00,
            'recommendation' => InspectionRecommendation::Recommended,
            'summary_note' => 'Kondisi kendaraan sangat prima dan siap pakai.',
            'submitted_at' => now()->subHours(6),
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
            'rating' => ItemRating::Good,
            'note' => 'Mesin kering dan terawat sempurna.',
        ]);

        $photoFilename = Str::uuid().'.jpg';
        $photoPath = "results/{$result->id}/photos/{$photoFilename}";
        Storage::disk('private')->put($photoPath, 'fake-private-photo-bytes');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $photoPath,
            'captured_at' => now()->subHours(6),
            'stamped_at' => now()->subHours(6),
            'is_camera_capture' => true,
        ]);

        return [
            'seller' => $seller,
            'requester' => $requester,
            'inspector' => $inspector,
            'listing' => $listing,
            'job' => $job,
            'payment' => $payment,
            'template' => $template,
            'templateItem' => $templateItem,
            'result' => $result,
            'resultItem' => $resultItem,
            'photo' => $photo,
        ];
    }
}
