<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Feature;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Models\Payment;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultAccess;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ViewResultPhotoAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');
    }

    #[Test]
    public function buyer_with_paid_access_can_view_photo_via_signed_url(): void
    {
        $inspector = User::factory()->inspector()->create(['status' => UserStatus::Active]);
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $result = InspectionResult::factory()->submitted()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Submitted,
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
        ]);

        $path = "results/{$result->id}/photos/engine.jpg";
        Storage::disk('private')->put($path, 'photo content');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $buyer = User::factory()->user()->create(['status' => UserStatus::Active]);
        $payment = Payment::factory()->success()->access()->create([
            'payer_id' => $buyer->id,
            'ref_id' => $result->id,
        ]);

        ResultAccess::factory()
            ->for($result, 'result')
            ->for($buyer, 'user')
            ->create(['payment_id' => $payment->id]);

        $signedUrl = $photo->temporaryUrl(15);

        // Buyer with paid access should be able to view photo (200 OK)
        $this->actingAs($buyer)->get($signedUrl)->assertOk();
    }

    #[Test]
    public function user_without_access_is_forbidden_from_viewing_photo(): void
    {
        $inspector = User::factory()->inspector()->create(['status' => UserStatus::Active]);
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $result = InspectionResult::factory()->submitted()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Submitted,
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
        ]);

        $path = "results/{$result->id}/photos/engine.jpg";
        Storage::disk('private')->put($path, 'photo content');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $stranger = User::factory()->user()->create(['status' => UserStatus::Active]);
        $signedUrl = $photo->temporaryUrl(15);

        // User without access should receive 403 Forbidden
        $this->actingAs($stranger)->get($signedUrl)->assertForbidden();
    }

    #[Test]
    public function draft_result_photo_cannot_be_viewed_by_buyer_even_with_access_record(): void
    {
        $inspector = User::factory()->inspector()->create(['status' => UserStatus::Active]);
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Draft,
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
        ]);

        $path = "results/{$result->id}/photos/draft.jpg";
        Storage::disk('private')->put($path, 'draft photo content');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $buyer = User::factory()->user()->create(['status' => UserStatus::Active]);
        $payment = Payment::factory()->success()->access()->create([
            'payer_id' => $buyer->id,
            'ref_id' => $result->id,
        ]);

        ResultAccess::factory()
            ->for($result, 'result')
            ->for($buyer, 'user')
            ->create(['payment_id' => $payment->id]);

        $signedUrl = $photo->temporaryUrl(15);

        // Draft result photo cannot be viewed by other users (403 Forbidden)
        $this->actingAs($buyer)->get($signedUrl)->assertForbidden();
    }

    #[Test]
    public function blocked_or_inactive_user_is_forbidden_from_viewing_photo(): void
    {
        $inspector = User::factory()->inspector()->create(['status' => UserStatus::Active]);
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create(['template_id' => $template->id]);

        $result = InspectionResult::factory()->submitted()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Submitted,
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
        ]);

        $path = "results/{$result->id}/photos/engine.jpg";
        Storage::disk('private')->put($path, 'photo content');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $blockedBuyer = User::factory()->user()->create(['status' => UserStatus::Blocked]);
        $pendingBuyer = User::factory()->user()->create(['status' => UserStatus::Pending]);

        ResultAccess::factory()
            ->for($result, 'result')
            ->for($blockedBuyer, 'user')
            ->create();

        ResultAccess::factory()
            ->for($result, 'result')
            ->for($pendingBuyer, 'user')
            ->create();

        $signedUrl = $photo->temporaryUrl(15);

        // Inactive/pending user is rejected by policy with 403 Forbidden
        $this->actingAs($pendingBuyer)->get($signedUrl)->assertForbidden();

        // Blocked user is rejected by middleware redirect and policy check
        $this->actingAs($blockedBuyer)->get($signedUrl)->assertRedirect(route('identity.blocked'));
        $this->assertFalse(Gate::forUser($blockedBuyer)->allows('viewPhoto', [$result, $photo]));
    }
}
