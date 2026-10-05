<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Actions\UploadInspectorDocument;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Exceptions\DocumentUploadException;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
});

it('ONB-T01 Unggah dokumen valid tersimpan di disk privat', function () {
    $inspector = User::factory()->inspector()->pending()->create(['name' => 'Ahmad Teknisi']);
    $action = app(UploadInspectorDocument::class);

    // Real JPEG image content
    $imageFile = UploadedFile::fake()->image('ahmad_ktp.jpg', 640, 480);

    $document = $action->handle($inspector, DocumentType::Ktp, $imageFile);

    // Stored in private disk
    Storage::disk('private')->assertExists($document->storage_path);

    // NOT stored in public disk
    Storage::disk('public')->assertMissing($document->storage_path);

    // Filename is randomized (does not contain inspector real name or original file name)
    expect($document->storage_path)->not->toContain('ahmad')
        ->and($document->storage_path)->not->toContain('ahmad_ktp.jpg');

    // Storage path is not accessible via public URL
    get('/storage/'.$document->storage_path)->assertForbidden();
});

it('ONB-T02 Berkas terlalu besar atau tipe salah ditolak', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $action = app(UploadInspectorDocument::class);

    // ONB-E1: Berkas > 2 MB ditolak
    $oversizedContent = "%PDF-1.4\n".str_repeat('X', (int) (2.1 * 1024 * 1024))."\n%%EOF";
    $oversizedFile = UploadedFile::fake()->createWithContent('besar.pdf', $oversizedContent);

    expect(fn () => $action->handle($inspector, DocumentType::Cv, $oversizedFile))
        ->toThrow(DocumentUploadException::class);

    // ONB-E2: Berkas dengan ekstensi .pdf tetapi isi bukan PDF ditolak (finfo check)
    $fakePdfFile = UploadedFile::fake()->createWithContent('fake.pdf', '<?php echo "infiltrate system"; ?>');

    expect(fn () => $action->handle($inspector, DocumentType::Cv, $fakePdfFile))
        ->toThrow(DocumentUploadException::class);
});

it('ONB-T03 Inspektor tanpa dokumen lengkap tidak masuk antrean', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $profile = InspectorProfile::factory()->pending()->create(['user_id' => $inspector->id]);

    // Belum mengunggah semua dokumen: tidak masuk antrean dan tampil daftar yang kurang (ONB-E7)
    expect($profile->hasCompleteDocuments())->toBeFalse()
        ->and($profile->isReadyForVerification())->toBeFalse()
        ->and(InspectorProfile::query()->readyForVerification()->where('id', $profile->id)->exists())->toBeFalse();

    $missingTypes = $profile->missingDocumentTypes();
    expect($missingTypes)->toHaveCount(4);

    // Unggah KTP, Sertifikat, CV (kurang Portfolio)
    InspectorDocument::factory()->ktp()->create(['user_id' => $inspector->id]);
    InspectorDocument::factory()->certificate()->create(['user_id' => $inspector->id]);
    InspectorDocument::factory()->cv()->create(['user_id' => $inspector->id]);

    $profile->refresh();
    expect($profile->hasCompleteDocuments())->toBeFalse()
        ->and(InspectorProfile::query()->readyForVerification()->where('id', $profile->id)->exists())->toBeFalse()
        ->and($profile->missingDocumentTypes())->toEqual([DocumentType::Portfolio]);

    // Lengkapi Portofolio: sekarang masuk antrean
    InspectorDocument::factory()->portfolio()->create(['user_id' => $inspector->id]);

    $profile->refresh();
    expect($profile->hasCompleteDocuments())->toBeTrue()
        ->and($profile->isReadyForVerification())->toBeTrue()
        ->and(InspectorProfile::query()->readyForVerification()->where('id', $profile->id)->exists())->toBeTrue()
        ->and($profile->missingDocumentTypes())->toHaveCount(0);
});

it('ONB-T07 Hanya pemilik dan admin yang dapat mengakses dokumen', function (string $actorRole, int $expectedStatus) {
    $owner = User::factory()->inspector()->pending()->create();
    $filePath = "inspector-documents/{$owner->id}/rahasia.pdf";
    Storage::disk('private')->put($filePath, "%PDF-1.4\n%real pdf content\n%%EOF");

    $document = InspectorDocument::factory()->create([
        'user_id' => $owner->id,
        'storage_path' => $filePath,
        'metadata' => [
            'original_filename' => 'ktp_asli.pdf',
            'mime_type' => 'application/pdf',
        ],
    ]);

    $signedUrl = $document->getTemporarySignedUrl(30);

    if ($actorRole === 'guest') {
        // Guest is redirected to login
        get($signedUrl)->assertRedirect(route('login'));

        return;
    }

    $actor = match ($actorRole) {
        'owner' => $owner,
        'admin' => User::factory()->admin()->active()->create(),
        'other_inspector' => User::factory()->inspector()->pending()->create(),
        'regular_user' => User::factory()->user()->active()->create(),
        default => throw new InvalidArgumentException("Unexpected role: {$actorRole}"),
    };

    actingAs($actor);
    $response = get($signedUrl);

    $response->assertStatus($expectedStatus);

    if ($expectedStatus === 200) {
        $response->assertHeader('Content-Type', 'application/pdf');
        // Storage path is never revealed in response content or headers
        expect($response->headers->get('Content-Disposition'))->toContain('ktp_asli.pdf')
            ->and($response->headers->get('Content-Disposition'))->not->toContain($filePath);
    }
})->with([
    'document owner' => ['owner', 200],
    'admin user' => ['admin', 200],
    'other inspector' => ['other_inspector', 403],
    'regular user' => ['regular_user', 403],
    'unauthenticated guest' => ['guest', 302],
]);

it('ONB-T07 rejects expired signed URLs with 403', function () {
    $owner = User::factory()->inspector()->pending()->create();
    $filePath = "inspector-documents/{$owner->id}/rahasia.pdf";
    Storage::disk('private')->put($filePath, "%PDF-1.4\n%%EOF");

    $document = InspectorDocument::factory()->create([
        'user_id' => $owner->id,
        'storage_path' => $filePath,
    ]);

    // Create URL expired 5 minutes ago
    Carbon::setTestNow(now());
    $expiredUrl = URL::temporarySignedRoute(
        'inspector.documents.view',
        now()->subMinutes(5),
        ['document' => $document->id]
    );

    actingAs($owner);
    get($expiredUrl)->assertForbidden();
});

it('ONB-T07 rejects tampered signed URLs with 403', function () {
    $owner = User::factory()->inspector()->pending()->create();
    $filePath = "inspector-documents/{$owner->id}/rahasia.pdf";
    Storage::disk('private')->put($filePath, "%PDF-1.4\n%%EOF");

    $document = InspectorDocument::factory()->create([
        'user_id' => $owner->id,
        'storage_path' => $filePath,
    ]);

    $validUrl = $document->getTemporarySignedUrl(30);
    $tamperedUrl = $validUrl.'&tampered=1';

    actingAs($owner);
    get($tamperedUrl)->assertForbidden();
});
