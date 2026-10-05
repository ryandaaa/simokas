<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Models\InspectorDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('tests inspector document methods for stored files and urls', function () {
    $user = User::factory()->inspector()->pending()->create();

    // Stored file document
    $fileDoc = InspectorDocument::factory()->ktp()->create([
        'user_id' => $user->id,
        'storage_path' => 'inspector-documents/'.$user->id.'/ktp-test.pdf',
    ]);

    expect($fileDoc->isStoredFile())->toBeTrue()
        ->and($fileDoc->isUrl())->toBeFalse()
        ->and($fileDoc->isSafeUrl())->toBeFalse()
        ->and($fileDoc->getSafeUrl())->toBeNull()
        ->and($fileDoc->getUrl())->not->toBeNull()
        ->and($fileDoc->getUrl())->toContain('inspector/documents/'.$fileDoc->id)
        ->and($fileDoc->user->id)->toBe($user->id);

    // Safe URL document
    $safeUrlDoc = InspectorDocument::factory()->create([
        'user_id' => $user->id,
        'type' => DocumentType::Portfolio,
        'storage_path' => null,
        'metadata' => [
            'original_filename' => 'https://github.com/my-portfolio',
            'url' => 'https://github.com/my-portfolio',
        ],
    ]);

    expect($safeUrlDoc->isStoredFile())->toBeFalse()
        ->and($safeUrlDoc->isUrl())->toBeTrue()
        ->and($safeUrlDoc->isSafeUrl())->toBeTrue()
        ->and($safeUrlDoc->getSafeUrl())->toBe('https://github.com/my-portfolio')
        ->and($safeUrlDoc->getUrl())->toBe('https://github.com/my-portfolio');

    // Unsafe URL document
    $unsafeUrlDoc = InspectorDocument::factory()->create([
        'user_id' => $user->id,
        'type' => DocumentType::Portfolio,
        'storage_path' => null,
        'metadata' => [
            'original_filename' => 'javascript:alert(1)',
            'url' => 'javascript:alert(1)',
        ],
    ]);

    expect($unsafeUrlDoc->isStoredFile())->toBeFalse()
        ->and($unsafeUrlDoc->isUrl())->toBeTrue()
        ->and($unsafeUrlDoc->isSafeUrl())->toBeFalse()
        ->and($unsafeUrlDoc->getSafeUrl())->toBeNull()
        ->and($unsafeUrlDoc->getUrl())->toBeNull();

    // Empty document with neither file nor url
    $emptyDoc = new InspectorDocument;
    expect($emptyDoc->getUrl())->toBeNull();
});
