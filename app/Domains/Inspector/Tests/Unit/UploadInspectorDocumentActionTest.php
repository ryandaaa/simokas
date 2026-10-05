<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Actions\UploadInspectorDocument;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Exceptions\DocumentUploadException;
use App\Domains\Inspector\Exceptions\InvalidInspectorProfileException;
use App\Domains\Inspector\Models\InspectorDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
});

it('uploads valid document and stores it in private disk with randomized filename', function () {
    $inspector = User::factory()->inspector()->pending()->create(['name' => 'Budi Rahasia']);
    $action = new UploadInspectorDocument;

    $file = UploadedFile::fake()->createWithContent('budi_asli_ktp.pdf', "%PDF-1.4\n%...\n%%EOF");

    $document = $action->handle($inspector, DocumentType::Ktp, $file);

    expect($document)->toBeInstanceOf(InspectorDocument::class)
        ->and($document->user_id)->toBe($inspector->id)
        ->and($document->type)->toBe(DocumentType::Ktp)
        ->and($document->storage_path)->not->toBeNull()
        ->and($document->storage_path)->not->toContain('budi')
        ->and($document->storage_path)->not->toContain('ktp.pdf');

    Storage::disk('private')->assertExists($document->storage_path);

    expect($document->metadata['original_filename'])->toBe('budi_asli_ktp.pdf')
        ->and($document->metadata['mime_type'])->toBe('application/pdf');
});

it('rejects files larger than 2 MB', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $action = new UploadInspectorDocument;

    // 2.5 MB PDF
    $content = "%PDF-1.4\n".str_repeat('A', (int) (2.5 * 1024 * 1024))."\n%%EOF";
    $file = UploadedFile::fake()->createWithContent('big_cert.pdf', $content);

    expect(fn () => $action->handle($inspector, DocumentType::Certificate, $file))
        ->toThrow(DocumentUploadException::class);
});

it('rejects file when extension is valid but real MIME does not match allowed types', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $action = new UploadInspectorDocument;

    // File with .pdf extension but containing plain text or PHP script
    $file = UploadedFile::fake()->createWithContent('fake.pdf', '<?php echo "infiltrate"; ?>');

    expect(fn () => $action->handle($inspector, DocumentType::Cv, $file))
        ->toThrow(DocumentUploadException::class);
});

it('rejects uploading more than 3 certificates', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $action = new UploadInspectorDocument;

    for ($i = 1; $i <= 3; $i++) {
        $file = UploadedFile::fake()->createWithContent("cert_{$i}.pdf", "%PDF-1.4\n%%EOF");
        $action->handle($inspector, DocumentType::Certificate, $file);
    }

    expect(InspectorDocument::query()->where('user_id', $inspector->id)->where('type', DocumentType::Certificate->value)->count())->toBe(3);

    // 4th certificate attempt
    $fourthFile = UploadedFile::fake()->createWithContent('cert_4.pdf', "%PDF-1.4\n%%EOF");

    expect(fn () => $action->handle($inspector, DocumentType::Certificate, $fourthFile))
        ->toThrow(DocumentUploadException::class);
});

it('rejects duplicate uploads for single-document types', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $action = new UploadInspectorDocument;

    $file1 = UploadedFile::fake()->createWithContent('ktp_1.pdf', "%PDF-1.4\n%%EOF");
    $action->handle($inspector, DocumentType::Ktp, $file1);

    $file2 = UploadedFile::fake()->createWithContent('ktp_2.pdf', "%PDF-1.4\n%%EOF");

    expect(fn () => $action->handle($inspector, DocumentType::Ktp, $file2))
        ->toThrow(DocumentUploadException::class);
});

it('saves portfolio as URL link correctly', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $action = new UploadInspectorDocument;

    $url = 'https://portofolio.example.com/inspector-profile';
    $document = $action->handle($inspector, DocumentType::Portfolio, $url);

    expect($document->isUrl())->toBeTrue()
        ->and($document->isStoredFile())->toBeFalse()
        ->and($document->storage_path)->toBeNull()
        ->and($document->metadata['url'])->toBe($url);
});

it('rejects invalid or dangerous portfolio URL strings', function (string $dangerousUrl) {
    $inspector = User::factory()->inspector()->pending()->create();
    $action = new UploadInspectorDocument;

    expect(fn () => $action->handle($inspector, DocumentType::Portfolio, $dangerousUrl))
        ->toThrow(DocumentUploadException::class);
})->with([
    'plain invalid string' => ['bukan-url-yang-valid'],
    'javascript with newline xss' => ['javascript://x%0Aalert(1)'],
    'javascript scheme' => ['javascript:alert(1)'],
    'data scheme' => ['data://text/html,x'],
    'vbscript scheme' => ['vbscript:msgbox(1)'],
    'file scheme' => ['file:///etc/passwd'],
    'userinfo url' => ['https://user:pass@evil.com'],
    'spaces in url' => ['https://example.com/bad url'],
]);

it('rejects document upload from non-inspector and non-admin users', function () {
    $user = User::factory()->user()->active()->create();
    $action = new UploadInspectorDocument;

    $file = UploadedFile::fake()->createWithContent('ktp.pdf', "%PDF-1.4\n%%EOF");

    expect(fn () => $action->handle($user, DocumentType::Ktp, $file))
        ->toThrow(InvalidInspectorProfileException::class);
});
