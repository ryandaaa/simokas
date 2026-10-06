<?php

declare(strict_types=1);

use App\Domains\Admin\Livewire\InspectionTemplateManager;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use App\Domains\Result\Actions\GetOrCreateDraftResult;
use App\Domains\Result\Contracts\TemplateEditor;
use App\Domains\Result\Database\seeders\InspectionTemplateSeeder;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\InspectionTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('ADM-T12 renders template list for all vehicle types', function () {
    (new InspectionTemplateSeeder)->run();

    $admin = User::factory()->admin()->create();
    actingAs($admin);

    Livewire::test(InspectionTemplateManager::class)
        ->assertOk()
        ->assertSee(__('admin.templates.title'))
        ->assertSee(__('admin.templates.vehicle_type_car'))
        ->assertSee(__('admin.templates.vehicle_type_motorcycle'));
});

it('ADM-T12 increases template version creating new template while old template and items remain unchanged', function () {
    (new InspectionTemplateSeeder)->run();

    $admin = User::factory()->admin()->create();
    actingAs($admin);

    $template = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    $initialVersion = $template->version;
    $items = $template->items()->orderBy('sort_order')->get();
    $initialItemCount = $items->count();

    expect($initialVersion)->toBe(1);
    expect($initialItemCount)->toBeGreaterThan(0);

    $originalItem = $items->first();
    $originalLabel = $originalItem->label;
    $originalWeight = $originalItem->weight;
    $originalRequired = $originalItem->is_required;
    $originalPhoto = $originalItem->requires_photo;
    $originalCritical = $originalItem->is_critical;
    $originalSortOrder = $originalItem->sort_order;

    // Update an item property via Livewire
    Livewire::test(InspectionTemplateManager::class)
        ->call('selectTemplate', $template->id)
        ->set('items.0.is_required', ! $originalRequired)
        ->call('saveItems')
        ->assertHasNoErrors()
        ->assertSee(__('admin.templates.save_success', ['version' => $initialVersion + 1]));

    // (a) Old template is now inactive, version unchanged, items identical to before edit
    $template->refresh();
    expect($template->version)->toBe($initialVersion);
    expect($template->is_active)->toBeFalse();

    $originalItem->refresh();
    expect($originalItem->label)->toBe($originalLabel);
    expect($originalItem->weight)->toBe($originalWeight);
    expect($originalItem->is_required)->toBe($originalRequired);
    expect($originalItem->requires_photo)->toBe($originalPhoto);
    expect($originalItem->is_critical)->toBe($originalCritical);
    expect($originalItem->sort_order)->toBe($originalSortOrder);

    // (a) New template exists with version + 1 and is_active true
    $newTemplate = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    expect($newTemplate->id)->not->toBe($template->id);
    expect($newTemplate->version)->toBe($initialVersion + 1);
    expect($newTemplate->is_active)->toBeTrue();
    expect($newTemplate->name)->toBe($template->name);
    expect($newTemplate->items)->toHaveCount($initialItemCount);

    $newItem = $newTemplate->items()->first();
    expect($newItem->id)->not->toBe($originalItem->id);
    expect($newItem->is_required)->toBe(! $originalRequired);
});

it('ADM-T12 old results maintain their template_id and old items are unchanged after template update', function () {
    (new InspectionTemplateSeeder)->run();

    $admin = User::factory()->admin()->create();
    $inspector = User::factory()->inspector()->create();

    // Create job with result using current template
    $template = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    $originalItem = $template->items()->first();
    $originalLabel = $originalItem->label;
    $originalCritical = $originalItem->is_critical;

    $listing = Listing::factory()->create(['vehicle_type' => VehicleType::Car]);
    $job = InspectionJob::factory()->create(['listing_id' => $listing->id]);

    $result = InspectionResult::factory()
        ->for($job, 'job')
        ->for($template, 'template')
        ->for($inspector, 'inspector')
        ->create();

    // Verify result was created with template version 1
    expect($result->template_id)->toBe($template->id);
    expect($template->version)->toBe(1);

    // Now update the template to version 2
    actingAs($admin);

    Livewire::test(InspectionTemplateManager::class)
        ->call('selectTemplate', $template->id)
        ->set('items.0.label', 'Edited Label')
        ->set('items.0.is_critical', ! $originalCritical)
        ->call('saveItems')
        ->assertHasNoErrors();

    // New active template created
    $newTemplate = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    expect($newTemplate->id)->not->toBe($template->id);
    expect($newTemplate->version)->toBe(2);

    // (b) Old result still points to old template_id, old template version is 1, old items unchanged
    $result->refresh();
    expect($result->template_id)->toBe($template->id);
    expect($result->template->version)->toBe(1);
    expect($result->template->is_active)->toBeFalse();

    $originalItem->refresh();
    expect($originalItem->label)->toBe($originalLabel);
    expect($originalItem->is_critical)->toBe($originalCritical);
});

it('ADM-T12 ongoing draft maintains old template while new draft uses new template', function () {
    (new InspectionTemplateSeeder)->run();

    $admin = User::factory()->admin()->create();
    $inspector = User::factory()->inspector()->create();

    // Create job 1 with draft result using current template
    $listing1 = Listing::factory()->create(['vehicle_type' => VehicleType::Car]);
    $job1 = InspectionJob::factory()
        ->inProgress($inspector)
        ->create(['listing_id' => $listing1->id]);

    $oldTemplate = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    $draftResult1 = app(GetOrCreateDraftResult::class)->handle($inspector, $job1);

    expect($draftResult1->template_id)->toBe($oldTemplate->id);
    expect($draftResult1->template->version)->toBe(1);

    // Update template to version 2
    actingAs($admin);

    Livewire::test(InspectionTemplateManager::class)
        ->call('selectTemplate', $oldTemplate->id)
        ->set('items.0.requires_photo', true)
        ->call('saveItems')
        ->assertHasNoErrors();

    $newTemplate = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    expect($newTemplate->version)->toBe(2);
    expect($newTemplate->id)->not->toBe($oldTemplate->id);

    // (c) Ongoing draft still references old template
    $draftResult1->refresh();
    expect($draftResult1->template_id)->toBe($oldTemplate->id);
    expect($draftResult1->template->version)->toBe(1);

    // (c) Fetching draft again for existing job continues to return draft with old template
    $reloadedDraft = app(GetOrCreateDraftResult::class)->handle($inspector, $job1);
    expect($reloadedDraft->template_id)->toBe($oldTemplate->id);

    // (c) New draft created after edit uses new template
    $listing2 = Listing::factory()->create(['vehicle_type' => VehicleType::Car]);
    $job2 = InspectionJob::factory()
        ->inProgress($inspector)
        ->create(['listing_id' => $listing2->id]);

    $draftResult2 = app(GetOrCreateDraftResult::class)->handle($inspector, $job2);
    expect($draftResult2->template_id)->toBe($newTemplate->id);
    expect($draftResult2->template->version)->toBe(2);
});

it('ADM-T12 reordering items produces new sort_order only on the new template', function () {
    (new InspectionTemplateSeeder)->run();

    $admin = User::factory()->admin()->create();
    actingAs($admin);

    $template = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    $initialVersion = $template->version;
    $originalFirstItem = $template->items()->orderBy('sort_order')->first();
    $originalSecondItem = $template->items()->orderBy('sort_order')->skip(1)->first();
    $originalThirdItem = $template->items()->orderBy('sort_order')->skip(2)->first();

    $originalOrder1 = $originalFirstItem->sort_order;
    $originalOrder2 = $originalSecondItem->sort_order;
    $originalOrder3 = $originalThirdItem->sort_order;

    Livewire::test(InspectionTemplateManager::class)
        ->call('selectTemplate', $template->id)
        ->set('items.0.sort_order', 3)
        ->set('items.1.sort_order', 1)
        ->set('items.2.sort_order', 2)
        ->call('saveItems')
        ->assertHasNoErrors();

    // (d) Verify old template items sort orders are unchanged
    $originalFirstItem->refresh();
    $originalSecondItem->refresh();
    $originalThirdItem->refresh();

    expect($originalFirstItem->sort_order)->toBe($originalOrder1);
    expect($originalSecondItem->sort_order)->toBe($originalOrder2);
    expect($originalThirdItem->sort_order)->toBe($originalOrder3);

    // (d) Verify new template has the new sort orders
    $newTemplate = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    expect($newTemplate->version)->toBe($initialVersion + 1);

    $newItem1 = $newTemplate->items()->where('label', $originalFirstItem->label)->first();
    $newItem2 = $newTemplate->items()->where('label', $originalSecondItem->label)->first();
    $newItem3 = $newTemplate->items()->where('label', $originalThirdItem->label)->first();

    expect($newItem1->sort_order)->toBe(3);
    expect($newItem2->sort_order)->toBe(1);
    expect($newItem3->sort_order)->toBe(2);
});

it('ADM-T12 denies non-admin users access to template management', function (string $role) {
    (new InspectionTemplateSeeder)->run();

    $factory = User::factory();
    if ($role === 'buyer' || $role === 'seller') {
        $user = $factory->user()->create();
    } else {
        $user = $factory->{$role}()->create();
    }
    actingAs($user);

    Livewire::test(InspectionTemplateManager::class)
        ->assertForbidden();
})->with(['buyer', 'seller', 'inspector']);

it('ADM-T12 two consecutive saves from the same base version do not produce duplicate versions', function () {
    (new InspectionTemplateSeeder)->run();

    $template = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    expect($template->version)->toBe(1);

    $itemsData = $template->items()->get()->map(fn ($item) => [
        'id' => $item->id,
        'label' => $item->label.' Updated',
        'weight' => $item->weight,
        'is_required' => $item->is_required,
        'requires_photo' => $item->requires_photo,
        'is_critical' => $item->is_critical,
        'sort_order' => $item->sort_order,
    ])->toArray();

    $editor = app(TemplateEditor::class);

    // First save from base template creates version 2
    $v2 = $editor->handle($template, $itemsData);
    expect($v2->version)->toBe(2);
    expect($v2->is_active)->toBeTrue();

    // Second save from the same base template instance creates version 3
    $v3 = $editor->handle($template, $itemsData);
    expect($v3->version)->toBe(3);
    expect($v3->is_active)->toBeTrue();

    // (f) Verify all versions are distinct and unique constraint was not violated
    $versions = InspectionTemplate::where('vehicle_type', VehicleType::Car->value)
        ->pluck('version')
        ->sort()
        ->values()
        ->all();

    expect($versions)->toBe([1, 2, 3]);

    // Only the newest version is active
    $active = InspectionTemplate::where('vehicle_type', VehicleType::Car->value)->active()->get();
    expect($active)->toHaveCount(1);
    expect($active->first()->id)->toBe($v3->id);
});

it('validates required fields when updating items', function () {
    (new InspectionTemplateSeeder)->run();

    $admin = User::factory()->admin()->create();
    actingAs($admin);

    $template = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();

    Livewire::test(InspectionTemplateManager::class)
        ->call('selectTemplate', $template->id)
        ->set('items.0.label', '')
        ->call('saveItems')
        ->assertHasErrors(['items.0.label']);
});
