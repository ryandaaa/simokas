<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Feature;

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ViewResultPageItemLabelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function result_page_displays_item_label_and_item_note_without_showing_raw_uuid(): void
    {
        $seller = User::factory()->user()->create(['status' => UserStatus::Active]);
        $inspector = User::factory()->inspector()->create(['status' => UserStatus::Active]);
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $job->listing->update(['seller_id' => $seller->id]);

        $template = InspectionTemplate::factory()->car()->create();
        $templateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'label' => 'Kondisi Mesin Avanza',
        ]);

        $result = InspectionResult::factory()->submitted()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Submitted,
        ]);

        $itemNote = 'Blok mesin bersih, oli bening, tidak ada kebocoran.';
        $resultItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $templateItem->id,
            'rating' => ItemRating::Good,
            'note' => $itemNote,
        ]);

        $response = $this->actingAs($seller)->get(route('results.show', ['listing' => $job->listing_id]));
        $response->assertOk();

        // Must display template item label
        $response->assertSee('Kondisi Mesin Avanza');

        // Must display item note
        $response->assertSee($itemNote);

        // Must NOT display raw UUID of template_item_id
        $response->assertDontSee($templateItem->id);
    }

    #[Test]
    public function result_page_displays_fallback_lang_text_when_template_item_relation_is_null(): void
    {
        $seller = User::factory()->user()->create(['status' => UserStatus::Active]);
        $inspector = User::factory()->inspector()->create(['status' => UserStatus::Active]);
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $job->listing->update(['seller_id' => $seller->id]);

        $template = InspectionTemplate::factory()->car()->create();

        $result = InspectionResult::factory()->submitted()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Submitted,
        ]);

        $orphanUuid = (string) Str::uuid();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
        ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $orphanUuid,
            'rating' => ItemRating::Good,
            'note' => 'Catatan item yatim tanpa template.',
        ]);
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $response = $this->actingAs($seller)->get(route('results.show', ['listing' => $job->listing_id]));
        $response->assertOk();

        // Must display fallback text from lang
        $response->assertSee(__('result.unnamed_item'));

        // Must NOT display raw UUID
        $response->assertDontSee($orphanUuid);
    }
}
