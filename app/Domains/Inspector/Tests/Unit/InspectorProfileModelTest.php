<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('detects missing documents and complete documents correctly', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $profile = InspectorProfile::factory()->pending()->create(['user_id' => $inspector->id]);

    expect($profile->hasCompleteDocuments())->toBeFalse()
        ->and($profile->missingDocumentTypes())->toHaveCount(4);

    // Upload KTP
    InspectorDocument::factory()->ktp()->create(['user_id' => $inspector->id]);
    expect($profile->hasCompleteDocuments())->toBeFalse()
        ->and($profile->missingDocumentTypes())->toHaveCount(3);

    // Upload Certificate
    InspectorDocument::factory()->certificate()->create(['user_id' => $inspector->id]);
    expect($profile->hasCompleteDocuments())->toBeFalse()
        ->and($profile->missingDocumentTypes())->toHaveCount(2);

    // Upload CV
    InspectorDocument::factory()->cv()->create(['user_id' => $inspector->id]);
    expect($profile->hasCompleteDocuments())->toBeFalse()
        ->and($profile->missingDocumentTypes())->toHaveCount(1)
        ->and($profile->missingDocumentTypes()[0])->toBe(DocumentType::Portfolio);

    // Upload Portfolio
    InspectorDocument::factory()->portfolio()->create(['user_id' => $inspector->id]);
    expect($profile->hasCompleteDocuments())->toBeTrue()
        ->and($profile->missingDocumentTypes())->toHaveCount(0)
        ->and($profile->isReadyForVerification())->toBeTrue();
});

it('filters verification queue using scopeReadyForVerification', function () {
    // Inspector 1: complete documents & pending
    $inspector1 = User::factory()->inspector()->pending()->create();
    $profile1 = InspectorProfile::factory()->pending()->create(['user_id' => $inspector1->id]);
    InspectorDocument::factory()->ktp()->create(['user_id' => $inspector1->id]);
    InspectorDocument::factory()->certificate()->create(['user_id' => $inspector1->id]);
    InspectorDocument::factory()->cv()->create(['user_id' => $inspector1->id]);
    InspectorDocument::factory()->portfolio()->create(['user_id' => $inspector1->id]);

    // Inspector 2: incomplete documents (missing CV and Portfolio)
    $inspector2 = User::factory()->inspector()->pending()->create();
    $profile2 = InspectorProfile::factory()->pending()->create(['user_id' => $inspector2->id]);
    InspectorDocument::factory()->ktp()->create(['user_id' => $inspector2->id]);
    InspectorDocument::factory()->certificate()->create(['user_id' => $inspector2->id]);

    // Inspector 3: complete documents but already approved
    $inspector3 = User::factory()->inspector()->active()->create();
    $profile3 = InspectorProfile::factory()->approved()->create(['user_id' => $inspector3->id]);
    InspectorDocument::factory()->ktp()->create(['user_id' => $inspector3->id]);
    InspectorDocument::factory()->certificate()->create(['user_id' => $inspector3->id]);
    InspectorDocument::factory()->cv()->create(['user_id' => $inspector3->id]);
    InspectorDocument::factory()->portfolio()->create(['user_id' => $inspector3->id]);

    $queueResults = InspectorProfile::query()->readyForVerification()->pluck('id')->toArray();

    expect($queueResults)->toContain($profile1->id)
        ->and($queueResults)->not->toContain($profile2->id)
        ->and($queueResults)->not->toContain($profile3->id);
});
