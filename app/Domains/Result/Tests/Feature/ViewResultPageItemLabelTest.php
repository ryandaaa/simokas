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
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $orphanUuid,
            'rating' => ItemRating::Good,
            'note' => 'Catatan item yatim tanpa template.',
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $response = $this->actingAs($seller)->get(route('results.show', ['listing' => $job->listing_id]));
        $response->assertOk();

        // Must display fallback text from lang
        $response->assertSee(__('result.unnamed_item'));

        // Must NOT display raw UUID
        $response->assertDontSee($orphanUuid);
    }

    #[Test]
    public function test_res_t25_result_page_places_each_photo_inside_its_matching_item_card(): void
    {
        $seller = User::factory()->user()->create(['status' => UserStatus::Active]);
        $inspector = User::factory()->inspector()->create(['status' => UserStatus::Active]);
        $job = InspectionJob::factory()->inProgress($inspector)->create();
        $job->listing->update(['seller_id' => $seller->id]);

        $template = InspectionTemplate::factory()->car()->create();
        $stnkTemplateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'label' => 'STNK',
        ]);
        $bpkbTemplateItem = TemplateItem::factory()->create([
            'template_id' => $template->id,
            'label' => 'BPKB',
        ]);

        $result = InspectionResult::factory()->submitted()->create([
            'job_id' => $job->id,
            'listing_id' => $job->listing_id,
            'inspector_id' => $inspector->id,
            'template_id' => $template->id,
            'status' => ResultStatus::Submitted,
        ]);

        $stnkItem = ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $stnkTemplateItem->id,
            'rating' => ItemRating::Good,
        ]);
        ResultItem::factory()->create([
            'result_id' => $result->id,
            'template_item_id' => $bpkbTemplateItem->id,
            'rating' => ItemRating::Good,
        ]);

        ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $stnkItem->id,
            'storage_path' => "results/{$result->id}/photos/stnk.jpg",
        ]);
        ResultPhoto::factory()->create([
            'result_id' => $result->id,
            'result_item_id' => $stnkItem->id,
            'storage_path' => "results/{$result->id}/photos/stnk-detail.jpg",
        ]);

        $response = $this->actingAs($seller)->get(route('results.show', ['listing' => $job->listing_id]));
        $response->assertOk();

        $document = new \DOMDocument;
        $previousLibxmlSetting = libxml_use_internal_errors(true);
        $document->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlSetting);

        $xpath = new \DOMXPath($document);
        $stnkCard = $xpath->query('//article[contains(normalize-space(.), "STNK")]')->item(0);
        $bpkbCard = $xpath->query('//article[contains(normalize-space(.), "BPKB")]')->item(0);

        $this->assertNotNull($stnkCard);
        $this->assertNotNull($bpkbCard);

        // Kueri relatif terhadap kartu (konteks DOMNode) agar foto dihitung
        // per kartu item, bukan seluruh dokumen.
        $stnkImages = $xpath->query('.//img', $stnkCard);
        $bpkbImages = $xpath->query('.//img', $bpkbCard);

        $this->assertNotFalse($stnkImages);
        $this->assertNotFalse($bpkbImages);
        $this->assertSame(2, $stnkImages->length);
        $this->assertSame(0, $bpkbImages->length);

        $firstImage = $stnkImages->item(0);
        $this->assertInstanceOf(\DOMElement::class, $firstImage);
        $this->assertStringContainsString('signature=', $firstImage->getAttribute('src'));
        $this->assertStringContainsString(__('result.inspection_photo_for_item', ['item' => 'STNK']), $firstImage->getAttribute('alt'));
        $this->assertStringContainsString(__('result.item_photos_missing'), $bpkbCard->textContent);
    }
}
