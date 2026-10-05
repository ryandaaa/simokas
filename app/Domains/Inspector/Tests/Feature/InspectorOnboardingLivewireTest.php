<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Enums\VehicleType;
use App\Domains\Inspector\Livewire\InspectorOnboarding;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
});

it('restricts access to onboarding page by role', function () {
    $guestResponse = get(route('inspector.onboarding'));
    $guestResponse->assertRedirect(route('login'));

    $user = User::factory()->user()->active()->create();
    actingAs($user);
    get(route('inspector.onboarding'))->assertForbidden();

    $inspector = User::factory()->inspector()->pending()->create();
    actingAs($inspector);
    get(route('inspector.onboarding'))->assertOk();
});

it('renders onboarding component successfully with empty and loading states', function () {
    $inspector = User::factory()->inspector()->pending()->create();

    $component = Livewire::actingAs($inspector)
        ->test(InspectorOnboarding::class);

    $component->assertOk()
        ->assertSee(__('inspector.onboarding.profile_section'))
        ->assertSee(__('inspector.onboarding.documents_section'))
        ->assertSee(__('inspector.documents.file_requirements_ktp'))
        ->assertSee(__('inspector.onboarding.queue_missing_title'))
        ->assertSeeHtml('wire:loading.attr="disabled"');
});

it('saves inspector profile with vehicle types and experience years', function () {
    $inspector = User::factory()->inspector()->pending()->create();

    Livewire::actingAs($inspector)
        ->test(InspectorOnboarding::class)
        ->set('vehicle_types', [VehicleType::Car->value, VehicleType::Motorcycle->value])
        ->set('experience_years', 4)
        ->call('saveProfile')
        ->assertHasNoErrors()
        ->assertSee(__('inspector.onboarding.profile_saved'));

    $profile = InspectorProfile::query()->where('user_id', $inspector->id)->first();
    expect($profile)->not->toBeNull()
        ->and($profile->experience_years)->toBe(4)
        ->and($profile->vehicle_types)->toEqualCanonicalizing(['car', 'motorcycle']);
});

it('validates profile fields when saving', function () {
    $inspector = User::factory()->inspector()->pending()->create();

    Livewire::actingAs($inspector)
        ->test(InspectorOnboarding::class)
        ->set('vehicle_types', [])
        ->set('experience_years', 60)
        ->call('saveProfile')
        ->assertHasErrors(['vehicle_types', 'experience_years']);
});

it('uploads KTP document via Livewire', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $file = UploadedFile::fake()->createWithContent('my_ktp.pdf', "%PDF-1.4\n%%EOF");

    Livewire::actingAs($inspector)
        ->test(InspectorOnboarding::class)
        ->set('ktp_file', $file)
        ->call('uploadKtp')
        ->assertHasNoErrors()
        ->assertSee(__('inspector.documents.uploaded'));

    expect(InspectorDocument::query()->where('user_id', $inspector->id)->where('type', DocumentType::Ktp->value)->count())->toBe(1);
});

it('uploads certificate documents up to 3 times', function () {
    $inspector = User::factory()->inspector()->pending()->create();

    $test = Livewire::actingAs($inspector)->test(InspectorOnboarding::class);

    for ($i = 1; $i <= 3; $i++) {
        $file = UploadedFile::fake()->createWithContent("cert_{$i}.pdf", "%PDF-1.4\n%%EOF");
        $test->set('certificate_file', $file)
            ->call('uploadCertificate')
            ->assertHasNoErrors();
    }

    expect(InspectorDocument::query()->where('user_id', $inspector->id)->where('type', DocumentType::Certificate->value)->count())->toBe(3);
});

it('uploads CV document via Livewire', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $file = UploadedFile::fake()->createWithContent('curriculum_vitae.pdf', "%PDF-1.4\n%%EOF");

    Livewire::actingAs($inspector)
        ->test(InspectorOnboarding::class)
        ->set('cv_file', $file)
        ->call('uploadCv')
        ->assertHasNoErrors();

    expect(InspectorDocument::query()->where('user_id', $inspector->id)->where('type', DocumentType::Cv->value)->count())->toBe(1);
});

it('uploads portfolio as file or URL link', function () {
    $inspector1 = User::factory()->inspector()->pending()->create();
    $file = UploadedFile::fake()->createWithContent('portfolio.pdf', "%PDF-1.4\n%%EOF");

    // File upload
    Livewire::actingAs($inspector1)
        ->test(InspectorOnboarding::class)
        ->set('portfolio_mode', 'file')
        ->set('portfolio_file', $file)
        ->call('uploadPortfolio')
        ->assertHasNoErrors();

    expect(InspectorDocument::query()->where('user_id', $inspector1->id)->where('type', DocumentType::Portfolio->value)->count())->toBe(1);

    // URL upload
    $inspector2 = User::factory()->inspector()->pending()->create();
    Livewire::actingAs($inspector2)
        ->test(InspectorOnboarding::class)
        ->set('portfolio_mode', 'url')
        ->set('portfolio_url', 'https://github.com/my-portfolio')
        ->call('uploadPortfolio')
        ->assertHasNoErrors();

    $portfolioDoc = InspectorDocument::query()->where('user_id', $inspector2->id)->where('type', DocumentType::Portfolio->value)->first();
    expect($portfolioDoc)->not->toBeNull()
        ->and($portfolioDoc->metadata['url'])->toBe('https://github.com/my-portfolio');
});

it('handles document deletion with confirmation dialog', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $doc = InspectorDocument::factory()->ktp()->create(['user_id' => $inspector->id]);

    $test = Livewire::actingAs($inspector)
        ->test(InspectorOnboarding::class)
        ->call('confirmDeleteDocument', $doc->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('documentToDeleteId', $doc->id)
        ->assertSee(__('inspector.documents.confirm_delete_title'));

    // Cancel deletion
    $test->call('cancelDeleteDocument')
        ->assertSet('showDeleteModal', false)
        ->assertSet('documentToDeleteId', null);

    expect(InspectorDocument::query()->find($doc->id))->not->toBeNull();

    // Confirm and delete
    $test->call('confirmDeleteDocument', $doc->id)
        ->call('deleteDocument')
        ->assertSet('showDeleteModal', false)
        ->assertSet('documentToDeleteId', null)
        ->assertSee(__('inspector.documents.deleted'));

    expect(InspectorDocument::query()->find($doc->id))->toBeNull();
});

it('displays rejected alert when inspector profile is rejected', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    InspectorProfile::factory()->rejected('Foto KTP buram dan tidak terbaca')->create(['user_id' => $inspector->id]);

    Livewire::actingAs($inspector)
        ->test(InspectorOnboarding::class)
        ->assertSee(__('inspector.onboarding.rejected_alert_title'))
        ->assertSee('Foto KTP buram dan tidak terbaca');
});

it('displays approved alert when inspector profile is approved', function () {
    $inspector = User::factory()->inspector()->active()->create();
    InspectorProfile::factory()->approved()->create(['user_id' => $inspector->id]);

    Livewire::actingAs($inspector)
        ->test(InspectorOnboarding::class)
        ->assertSee(__('inspector.onboarding.approved_alert_title'));
});
