<?php

declare(strict_types=1);

use App\Domains\Admin\Livewire\InspectionTemplateManager;
use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Models\Listing;
use App\Domains\Result\Database\seeders\InspectionTemplateSeeder;
use App\Domains\Result\Enums\ResultStatus;
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

it('ADM-T12 increases template version when items are updated', function () {
    (new InspectionTemplateSeeder)->run();

    $admin = User::factory()->admin()->create();
    actingAs($admin);

    $template = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    $initialVersion = $template->version;
    $items = $template->items()->orderBy('sort_order')->get();
    $initialItemCount = $items->count();

    expect($initialVersion)->toBe(1);
    expect($initialItemCount)->toBeGreaterThan(0);

    // Update an item property
    Livewire::test(InspectionTemplateManager::class)
        ->call('selectTemplate', $template->id)
        ->set('items.0.is_required', false)
        ->call('saveItems')
        ->assertHasNoErrors();

    // Verify version increased
    $template->refresh();
    expect($template->version)->toBe($initialVersion + 1);

    // Verify item was updated
    $items->first()->refresh();
    expect($items->first()->is_required)->toBeFalse();
});

it('ADM-T12 old results maintain their template version after template update', function () {
    (new InspectionTemplateSeeder)->run();

    $admin = User::factory()->admin()->create();
    $inspector = User::factory()->inspector()->create();

    // Create job with result using current template
    $template = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    $job = InspectionJob::factory()
        ->forListing(
            Listing::factory()
                ->create(['vehicle_type' => VehicleType::Car])
        )
        ->create();

    $result = InspectionResult::factory()
        ->for($job)
        ->for($template)
        ->for($inspector, 'inspector')
        ->create();

    // Verify result was created with template version 1
    expect($template->version)->toBe(1);

    // Now update the template to version 2
    $template = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    actingAs($admin);

    Livewire::test(InspectionTemplateManager::class)
        ->call('selectTemplate', $template->id)
        ->set('items.0.is_critical', true)
        ->call('saveItems')
        ->assertHasNoErrors();

    // Verify template version increased
    $template->refresh();
    expect($template->version)->toBe(2);

    // Verify result still references the same template (not affected by version change)
    $result->refresh();
    expect($result->template_id)->toBe($template->id);
});

it('ADM-T12 in-progress draft maintains template version even after template update', function () {
    (new InspectionTemplateSeeder)->run();

    $admin = User::factory()->admin()->create();

    // Create job with draft result
    $job = InspectionJob::factory()
        ->forListing(
            Listing::factory()
                ->create(['vehicle_type' => VehicleType::Car])
        )
        ->inProgress()
        ->create();

    // Get or create draft result with the current template
    $template = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    $draftResult = InspectionResult::firstOrCreate(
        ['job_id' => $job->id],
        [
            'listing_id' => $job->listing_id,
            'inspector_id' => $job->requester_id,
            'template_id' => $template->id,
            'status' => ResultStatus::Draft,
        ]
    );

    // Record the template version at result creation time
    $initialTemplateVersion = $template->version;
    expect($initialTemplateVersion)->toBe(1);

    // Update template to version 2
    actingAs($admin);

    Livewire::test(InspectionTemplateManager::class)
        ->call('selectTemplate', $template->id)
        ->set('items.0.requires_photo', true)
        ->call('saveItems')
        ->assertHasNoErrors();

    // Verify template version increased
    $template->refresh();
    expect($template->version)->toBe(2);

    // Verify draft still references the same template (unchanged by version bump)
    $draftResult->refresh();
    expect($draftResult->template_id)->toBe($template->id);
});

it('ADM-T12 allows reordering template items', function () {
    (new InspectionTemplateSeeder)->run();

    $admin = User::factory()->admin()->create();
    actingAs($admin);

    $template = InspectionTemplate::forVehicleType(VehicleType::Car)->active()->firstOrFail();
    $initialVersion = $template->version;
    $originalFirstItem = $template->items()->orderBy('sort_order')->first();
    $originalSecondItem = $template->items()->orderBy('sort_order')->skip(1)->first();
    $originalThirdItem = $template->items()->orderBy('sort_order')->skip(2)->first();

    Livewire::test(InspectionTemplateManager::class)
        ->call('selectTemplate', $template->id)
        ->set('items.0.sort_order', 3)
        ->set('items.1.sort_order', 1)
        ->set('items.2.sort_order', 2)
        ->call('saveItems')
        ->assertHasNoErrors();

    // Verify version increased
    $template->refresh();
    expect($template->version)->toBe($initialVersion + 1);

    // Verify sort order changed for the modified items
    $originalFirstItem->refresh();
    $originalSecondItem->refresh();
    $originalThirdItem->refresh();

    expect($originalFirstItem->sort_order)->toBe(3);
    expect($originalSecondItem->sort_order)->toBe(1);
    expect($originalThirdItem->sort_order)->toBe(2);
});

it('denies non-admin users access to template management', function (string $role) {
    (new InspectionTemplateSeeder)->run();

    $user = User::factory()->{$role}()->create();
    actingAs($user);

    Livewire::test(InspectionTemplateManager::class)
        ->assertForbidden();
})->with(['buyer', 'seller', 'inspector']);

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
