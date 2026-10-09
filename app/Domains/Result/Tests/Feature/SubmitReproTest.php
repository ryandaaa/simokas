<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Result\Database\seeders\InspectionTemplateSeeder;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Livewire\InspectionFormPage;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\ResultPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class SubmitReproTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');
    }

    public function test_repro_full_submit_with_seeded_template(): void
    {
        $this->seed(InspectionTemplateSeeder::class);

        $inspector = User::factory()->create([
            'role' => UserRole::Inspector,
            'status' => UserStatus::Active,
        ]);
        InspectorProfile::factory()->create([
            'user_id' => $inspector->id,
            'verification_status' => VerificationStatus::Approved,
            'vehicle_types' => ['car', 'motorcycle'],
        ]);

        $job = InspectionJob::factory()->inProgress($inspector)->create();

        $template = InspectionTemplate::query()
            ->where('vehicle_type', VehicleType::Car)
            ->orderByDesc('version')
            ->firstOrFail();

        $this->actingAs($inspector);
        $component = Livewire::test(InspectionFormPage::class, ['job' => $job]);

        $resultId = $component->get('result')->id;

        foreach ($template->items as $templateItem) {
            $resultItem = ResultItem::factory()->create([
                'result_id' => $resultId,
                'template_item_id' => $templateItem->id,
                'rating' => ItemRating::Good,
            ]);

            if ($templateItem->requires_photo) {
                $path = "results/{$resultId}/photos/{$templateItem->id}.jpg";
                Storage::disk('private')->put($path, 'dummy');
                ResultPhoto::factory()->create([
                    'result_id' => $resultId,
                    'result_item_id' => $resultItem->id,
                    'storage_path' => $path,
                ]);
            }

            $component->set("items.{$templateItem->id}.rating", ItemRating::Good->value);
        }

        $component
            ->call('showSubmitConfirmation')
            ->call('submitResult')
            ->assertRedirect(route('jobs.show', $job->id));

        $this->assertDatabaseHas('inspection_results', [
            'id' => $resultId,
            'status' => ResultStatus::Submitted,
        ]);
    }
}
