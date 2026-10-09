<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Events\ResultSubmitted;
use App\Domains\Result\Livewire\InspectionFormPage;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class InspectionFormPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');
    }

    private function createVerifiedInspector(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Inspector,
            'status' => UserStatus::Active,
        ]);

        InspectorProfile::factory()->create([
            'user_id' => $user->id,
            'verification_status' => VerificationStatus::Approved,
            'vehicle_types' => ['car', 'motorcycle'],
        ]);

        return $user;
    }

    public function test_renders_camera_capture_for_items_requiring_photo(): void
    {
        $inspector = $this->createVerifiedInspector();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();

        $itemWithPhoto = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'category' => 'Mesin',
            'label' => 'Blok Mesin',
            'requires_photo' => true,
        ]);

        $itemWithoutPhoto = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'category' => 'Mesin',
            'label' => 'Minyak Rem',
            'requires_photo' => false,
        ]);

        $this->actingAs($inspector);

        Livewire::test(InspectionFormPage::class, ['job' => $job])
            ->assertSee('Blok Mesin')
            ->assertSee('Minyak Rem')
            ->assertSee(__('result.photos.title'))
            ->assertSee(__('result.photos.required_notice'))
            // Regression CAM-FIX: file input must NOT carry wire:model, otherwise
            // Livewire auto-uploads the raw photo and its re-render wipes Alpine state.
            ->assertDontSee('wire:model="tempPhotos.', false)
            ->assertDontSee('wire:use=', false)
            ->assertSee(':disabled="isProcessing"', false)
            ->assertSee(__('result.photos.processing'))
            ->assertSee(__('result.photos.uploading'));
    }

    public function test_inspector_can_capture_and_apply_photo_in_draft(): void
    {
        $inspector = $this->createVerifiedInspector();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();

        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'category' => 'Eksterior',
            'label' => 'Bodi Depan',
            'requires_photo' => true,
        ]);

        $file = UploadedFile::fake()->image('front_bumper.jpg', 800, 600);

        $this->actingAs($inspector);

        $component = Livewire::test(InspectionFormPage::class, ['job' => $job])
            ->set('tempPhotos.'.$templateItem->id, $file)
            ->call('applyPhoto', $templateItem->id, now()->toISOString());

        $component->assertHasNoErrors();
        $component->assertSee(__('result.photos.upload_success'));

        // Assert database record exists
        $this->assertDatabaseHas('result_photos', [
            'is_camera_capture' => true,
        ]);

        /** @var ResultPhoto $photo */
        $photo = ResultPhoto::query()->first();
        Storage::disk('private')->assertExists($photo->storage_path);

        // Component has the photo with signed URL
        $this->assertArrayHasKey($templateItem->id, $component->get('photos'));
        $this->assertStringContainsString('results/', $component->get('photos')[$templateItem->id]['url']);
    }

    public function test_inspector_can_retake_and_remove_photo(): void
    {
        $inspector = $this->createVerifiedInspector();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();

        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'category' => 'Interior',
            'label' => 'Speedometer',
            'requires_photo' => true,
        ]);

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
        ]);

        $path = "results/{$result->id}/photos/odometer.jpg";
        Storage::disk('private')->put($path, 'dummy odometer image');

        $photo = ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $this->actingAs($inspector);

        $component = Livewire::test(InspectionFormPage::class, ['job' => $job]);

        // Component initially has the photo
        $this->assertArrayHasKey($templateItem->id, $component->get('photos'));

        // Retake removes photo
        $component->call('retakePhoto', $templateItem->id);

        $component->assertSee(__('result.photos.delete_success'));
        Storage::disk('private')->assertMissing($path);
        $this->assertDatabaseMissing('result_photos', ['id' => $photo->id]);
        $this->assertArrayNotHasKey($templateItem->id, $component->get('photos'));
    }

    public function test_submit_failure_closes_confirmation_modal_so_error_is_visible(): void
    {
        $inspector = $this->createVerifiedInspector();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();

        // Required item without rating: submit must fail validation.
        TemplateItem::factory()->create([
            'template_id' => $template->id,
            'category' => 'Mesin',
            'label' => 'Blok Mesin',
            'requires_photo' => false,
        ]);

        $this->actingAs($inspector);

        Livewire::test(InspectionFormPage::class, ['job' => $job])
            ->call('openSubmitConfirmation')
            ->assertSet('showSubmitConfirmation', true)
            ->assertSet('submitModalKey', 1)
            // Membuka ulang harus menaikkan kunci agar DOM modal dibuat ulang
            // (modal yang pernah ditutup sepihak di Alpine tidak macet).
            ->call('openSubmitConfirmation')
            ->assertSet('submitModalKey', 2)
            ->call('submitResult')
            ->assertSet('showSubmitConfirmation', false)
            ->assertSee('Blok Mesin');

        // Status stays draft on failed submit.
        $this->assertDatabaseHas('inspection_results', [
            'job_id' => $job->id,
            'status' => ResultStatus::Draft,
        ]);
    }

    public function test_submit_validation_failure_closes_modal_and_shows_summary_error(): void
    {
        $inspector = $this->createVerifiedInspector();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();

        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'category' => 'Mesin',
            'label' => 'Blok Mesin',
            'requires_photo' => false,
        ]);

        $this->actingAs($inspector);

        Livewire::test(InspectionFormPage::class, ['job' => $job])
            ->set("items.{$templateItem->id}.note", str_repeat('a', 1001))
            ->call('openSubmitConfirmation')
            ->assertSet('showSubmitConfirmation', true)
            ->call('submitResult')
            ->assertSet('showSubmitConfirmation', false)
            ->assertHasErrors(["items.{$templateItem->id}.note"])
            ->assertSee(__('inspection.form.error_validation'));
    }

    public function test_submit_unexpected_error_closes_modal_and_shows_generic_message(): void
    {
        $inspector = $this->createVerifiedInspector();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();

        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'category' => 'Mesin',
            'label' => 'Blok Mesin',
            'requires_photo' => false,
        ]);

        // Simulasi listener event yang meledak di dalam transaksi submit.
        Event::listen(ResultSubmitted::class, function (): void {
            throw new \RuntimeException('boom');
        });

        $this->actingAs($inspector);

        Livewire::test(InspectionFormPage::class, ['job' => $job])
            ->set("items.{$templateItem->id}.rating", ItemRating::Good->value)
            ->call('openSubmitConfirmation')
            ->call('submitResult')
            ->assertSet('showSubmitConfirmation', false)
            ->assertSee(__('inspection.form.error_unexpected'));

        $this->assertDatabaseHas('inspection_results', [
            'job_id' => $job->id,
            'status' => ResultStatus::Draft,
        ]);
    }

    public function test_loading_existing_draft_loads_photos_with_signed_urls(): void
    {
        $inspector = $this->createVerifiedInspector();
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $template = InspectionTemplate::factory()->car()->create();

        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'category' => 'Kaki-kaki',
            'label' => 'Ban Depan',
            'requires_photo' => true,
        ]);

        $result = InspectionResult::factory()->draft()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
        ]);

        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
        ]);

        $path = "results/{$result->id}/photos/tire.jpg";
        Storage::disk('private')->put($path, 'dummy tire image');

        ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $resultItem->id,
            'storage_path' => $path,
        ]);

        $this->actingAs($inspector);

        $component = Livewire::test(InspectionFormPage::class, ['job' => $job]);

        $photos = $component->get('photos');
        $this->assertArrayHasKey($templateItem->id, $photos);
        $this->assertStringContainsString('signature=', $photos[$templateItem->id]['url']);
    }
}
