<?php

declare(strict_types=1);

namespace App\Domains\Result\Tests\Feature;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use App\Domains\Listing\Models\Listing;
use App\Domains\Result\Enums\ItemRating;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Livewire\InspectionFormPage;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function createVerifiedInspector(): User
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

function createInspectionSetup(string $vehicleType = 'car'): array
{
    $inspector = createVerifiedInspector();
    $listing = Listing::factory()->active()->create(['vehicle_type' => $vehicleType]);
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::InProgress,
        'taken_at' => now()->subHours(2),
        'started_at' => now()->subMinutes(10),
    ]);
    $template = InspectionTemplate::factory()->create([
        'vehicle_type' => $vehicleType,
        'is_active' => true,
        'version' => 1,
    ]);

    return [$inspector, $job, $template, $listing];
}

it('RES-T01 renders inspection form with correct template for car', function () {
    /** @var User $inspector */
    [$inspector, $job, $template] = createInspectionSetup('car');
    TemplateItem::factory()->count(3)->create([
        'template_id' => $template->id,
        'category' => 'Mesin',
    ]);

    actingAs($inspector);

    Livewire::test(InspectionFormPage::class, ['job' => $job])
        ->assertSet('result.template_id', $template->id)
        ->assertSee('Mesin');
});

it('RES-T01 renders inspection form with correct template for motorcycle', function () {
    /** @var User $inspector */
    [$inspector, $job, $template] = createInspectionSetup('motorcycle');
    TemplateItem::factory()->create([
        'template_id' => $template->id,
        'category' => 'Rangka Motor',
        'label' => 'Kondisi Rangka',
    ]);

    actingAs($inspector);

    Livewire::test(InspectionFormPage::class, ['job' => $job])
        ->assertSet('result.template_id', $template->id)
        ->assertSee('Rangka Motor');
});

it('RES-T02 saves draft and draft can be resumed after reload', function () {
    /** @var User $inspector */
    [$inspector, $job, $template] = createInspectionSetup();
    $templateItem = TemplateItem::factory()->create([
        'template_id' => $template->id,
        'category' => 'Mesin',
        'label' => 'Kondisi Mesin',
    ]);

    actingAs($inspector);

    // First mount: save draft
    $component = Livewire::test(InspectionFormPage::class, ['job' => $job])
        ->set("items.{$templateItem->id}.rating", ItemRating::Good->value)
        ->set("items.{$templateItem->id}.note", 'Mesin prima')
        ->set('summaryNote', 'Ringkasan inspeksi')
        ->call('saveDraft');

    $component->assertSee(__('inspection.form.draft_saved'));

    // Verify persisted in DB
    $result = InspectionResult::query()->where('job_id', $job->id)->first();
    expect($result)->not->toBeNull();
    expect($result->status)->toBe(ResultStatus::Draft);

    $savedItem = ResultItem::query()
        ->where('result_id', $result->id)
        ->where('template_item_id', $templateItem->id)
        ->first();
    expect($savedItem)->not->toBeNull();
    expect($savedItem->rating)->toBe(ItemRating::Good);
    expect($savedItem->note)->toBe('Mesin prima');

    // Re-mount (simulate reload) - draft should be restored
    $reloaded = Livewire::test(InspectionFormPage::class, ['job' => $job->refresh()]);
    $reloaded->assertSet("items.{$templateItem->id}.rating", ItemRating::Good->value);
    $reloaded->assertSet("items.{$templateItem->id}.note", 'Mesin prima');
});

it('RES-T14 rejects unauthenticated user from accessing inspection form', function () {
    [$inspector, $job] = createInspectionSetup();

    $response = get(route('result.fill', $job->id));
    $response->assertRedirect();
});

it('RES-T14 rejects regular user from accessing inspection form', function () {
    [$inspector, $job] = createInspectionSetup();
    $user = User::factory()->user()->create();

    $response = actingAs($user)->get(route('result.fill', $job->id));
    $response->assertStatus(403);
});

it('RES-T14 rejects other inspector from accessing draft', function () {
    /** @var User $inspector */
    [$inspector, $job, $template] = createInspectionSetup();
    $otherInspector = createVerifiedInspector();

    // Should get 403 (the Gate::authorize will fail)
    $response = actingAs($otherInspector)->get(route('result.fill', $job->id));
    $response->assertStatus(403);
});

it('RES-T14 allows only the assigned inspector to view draft via Livewire', function () {
    /** @var User $inspector */
    [$inspector, $job, $template] = createInspectionSetup();

    // Create a result belonging to this inspector
    $result = InspectionResult::factory()->draft()->create([
        'job_id' => $job->id,
        'inspector_id' => $inspector->id,
        'template_id' => $template->id,
        'listing_id' => $job->listing_id,
    ]);

    $otherInspector = createVerifiedInspector();
    actingAs($otherInspector);

    // Other inspector cannot call saveDraft on this result
    Livewire::test(InspectionFormPage::class, ['job' => $job])
        ->assertForbidden();
});

it('RES-T15 draft continues to use the template version at creation time', function () {
    /** @var User $inspector */
    [$inspector, $job, $templateV1] = createInspectionSetup();

    actingAs($inspector);

    // First mount creates result with v1 template
    $firstMount = Livewire::test(InspectionFormPage::class, ['job' => $job]);
    $result = InspectionResult::query()->where('job_id', $job->id)->first();
    expect($result)->not->toBeNull();
    expect($result->template_id)->toBe($templateV1->id);

    // Deactivate v1 and create v2
    $templateV1->update(['is_active' => false]);
    InspectionTemplate::factory()->create([
        'vehicle_type' => 'car',
        'is_active' => true,
        'version' => 2,
    ]);

    // Second mount should still use v1 (existing draft)
    Livewire::test(InspectionFormPage::class, ['job' => $job->refresh()])
        ->assertSet('result.template_id', $templateV1->id);
});

it('shows form only to assigned inspector on in_progress job', function () {
    /** @var User $inspector */
    [$inspector, $job, $template] = createInspectionSetup();
    TemplateItem::factory()->create([
        'template_id' => $template->id,
        'category' => 'Bodi',
        'label' => 'Kondisi Bodi',
    ]);

    actingAs($inspector);

    Livewire::test(InspectionFormPage::class, ['job' => $job])
        ->assertStatus(200)
        ->assertSee(__('inspection.form.title'));
});

it('auto-saves draft when navigating to next category', function () {
    /** @var User $inspector */
    [$inspector, $job, $template] = createInspectionSetup();

    $item1 = TemplateItem::factory()->create([
        'template_id' => $template->id,
        'category' => 'Mesin',
        'label' => 'Kondisi Mesin',
        'sort_order' => 1,
    ]);
    TemplateItem::factory()->create([
        'template_id' => $template->id,
        'category' => 'Bodi',
        'label' => 'Kondisi Bodi',
        'sort_order' => 10,
    ]);

    actingAs($inspector);

    Livewire::test(InspectionFormPage::class, ['job' => $job])
        ->set("items.{$item1->id}.rating", ItemRating::Fair->value)
        ->call('nextCategory');

    // Should have saved the draft automatically
    $result = InspectionResult::query()->where('job_id', $job->id)->first();
    expect($result)->not->toBeNull();

    $savedItem = ResultItem::query()
        ->where('result_id', $result->id)
        ->where('template_item_id', $item1->id)
        ->first();
    expect($savedItem)->not->toBeNull();
    expect($savedItem->rating)->toBe(ItemRating::Fair);
});

it('RES-T18 dispatches scroll event when changing inspection categories', function () {
    /** @var User $inspector */
    [$inspector, $job, $template] = createInspectionSetup();

    TemplateItem::factory()->create([
        'template_id' => $template->id,
        'category' => 'Mesin',
        'label' => 'Kondisi Mesin',
        'sort_order' => 1,
    ]);
    TemplateItem::factory()->create([
        'template_id' => $template->id,
        'category' => 'Bodi',
        'label' => 'Kondisi Bodi',
        'sort_order' => 10,
    ]);

    actingAs($inspector);

    // Pindah ke kategori berikutnya: indeks bertambah + event scroll dikirim agar HP kembali ke atas
    Livewire::test(InspectionFormPage::class, ['job' => $job])
        ->call('nextCategory')
        ->assertSet('currentCategoryIndex', 1)
        ->assertDispatched('inspection-category-changed')
        // Listener scroll tinggal di JS global (bukan x-data di root) agar state Alpine foto tidak tereset saat morph
        ->assertDontSee('x-on:inspection-category-changed', false);

    // Kembali ke kategori sebelumnya: event yang sama dikirim
    Livewire::test(InspectionFormPage::class, ['job' => $job->refresh()])
        ->set('currentCategoryIndex', 1)
        ->call('previousCategory')
        ->assertSet('currentCategoryIndex', 0)
        ->assertDispatched('inspection-category-changed');
});

it('shows job_not_in_progress error when job status is not in_progress', function () {
    $inspector = createVerifiedInspector();
    $listing = Listing::factory()->active()->create(['vehicle_type' => 'car']);
    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'inspector_id' => $inspector->id,
        'status' => JobStatus::Scheduled, // Not in_progress
    ]);

    InspectionTemplate::factory()->create([
        'vehicle_type' => 'car',
        'is_active' => true,
        'version' => 1,
    ]);

    // Should be forbidden because policy checks job status
    $response = actingAs($inspector)->get(route('result.fill', $job->id));
    $response->assertStatus(403);
});

it('RES-T14 rejects job requester from accessing inspection form', function () {
    [$inspector, $job] = createInspectionSetup();
    $requester = $job->requester;

    $response = actingAs($requester)->get(route('result.fill', $job->id));
    $response->assertStatus(403);
});

it('does not leak private listing data such as chassis number and address on form', function () {
    /** @var User $inspector */
    [$inspector, $job, $template, $listing] = createInspectionSetup();

    $listing->update([
        'chassis_number' => 'SECRET-CHASSIS-999',
        'address_full' => 'Jl. Rahasia No. 123 Gedung Tersembunyi',
    ]);

    $response = actingAs($inspector)->get(route('result.fill', $job->id));
    $response->assertStatus(200);
    $response->assertDontSee('SECRET-CHASSIS-999');
    $response->assertDontSee('Jl. Rahasia No. 123 Gedung Tersembunyi');
});

it('validates ratings and note character limits on save draft', function () {
    /** @var User $inspector */
    [$inspector, $job, $template] = createInspectionSetup();
    $templateItem = TemplateItem::factory()->create([
        'template_id' => $template->id,
    ]);

    actingAs($inspector);

    // Invalid rating
    Livewire::test(InspectionFormPage::class, ['job' => $job])
        ->set("items.{$templateItem->id}.rating", 'super_good')
        ->call('saveDraft')
        ->assertHasErrors(["items.{$templateItem->id}.rating"]);

    // Note too long (> 1000 chars)
    Livewire::test(InspectionFormPage::class, ['job' => $job])
        ->set("items.{$templateItem->id}.note", str_repeat('a', 1001))
        ->call('saveDraft')
        ->assertHasErrors(["items.{$templateItem->id}.note"]);

    // Summary note too long (> 2000 chars)
    Livewire::test(InspectionFormPage::class, ['job' => $job])
        ->set('summaryNote', str_repeat('b', 2001))
        ->call('saveDraft')
        ->assertHasErrors(['summaryNote']);
});

it('renders touch-friendly buttons and mobile-first layout suitable for 360px', function () {
    /** @var User $inspector */
    [$inspector, $job, $template] = createInspectionSetup();
    TemplateItem::factory()->create([
        'template_id' => $template->id,
        'category' => 'Mesin',
        'label' => 'Kondisi Mesin',
    ]);

    actingAs($inspector);

    $component = Livewire::test(InspectionFormPage::class, ['job' => $job]);

    // Check touch target height (min 44px / 56px for buttons)
    $component->assertSeeHtml('min-h-[56px]');
    // Check mobile-first grid and button layout
    $component->assertSeeHtml('grid grid-cols-3 gap-2');
    $component->assertSeeHtml('flex flex-col sm:flex-row');
});
