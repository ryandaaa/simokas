<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Actions\RemoveInspectorDocument;
use App\Domains\Inspector\Exceptions\DocumentUploadException;
use App\Domains\Inspector\Models\InspectorDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
});

it('allows owner to remove document and cleans up private storage file', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $filePath = "inspector-documents/{$inspector->id}/test-doc.pdf";
    Storage::disk('private')->put($filePath, "%PDF-1.4\n%%EOF");

    $document = InspectorDocument::factory()->create([
        'user_id' => $inspector->id,
        'storage_path' => $filePath,
    ]);

    $action = new RemoveInspectorDocument;
    $action->handle($inspector, $document);

    expect(InspectorDocument::query()->find($document->id))->toBeNull();
    Storage::disk('private')->assertMissing($filePath);
});

it('allows admin to remove document', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $admin = User::factory()->admin()->active()->create();
    $filePath = "inspector-documents/{$inspector->id}/admin-deleted.pdf";
    Storage::disk('private')->put($filePath, "%PDF-1.4\n%%EOF");

    $document = InspectorDocument::factory()->create([
        'user_id' => $inspector->id,
        'storage_path' => $filePath,
    ]);

    $action = new RemoveInspectorDocument;
    $action->handle($admin, $document);

    expect(InspectorDocument::query()->find($document->id))->toBeNull();
    Storage::disk('private')->assertMissing($filePath);
});

it('rejects unauthorized user when attempting to remove another user document', function () {
    $inspector1 = User::factory()->inspector()->pending()->create();
    $inspector2 = User::factory()->inspector()->pending()->create();

    $document = InspectorDocument::factory()->create([
        'user_id' => $inspector1->id,
    ]);

    $action = new RemoveInspectorDocument;

    expect(fn () => $action->handle($inspector2, $document))
        ->toThrow(DocumentUploadException::class);
});
