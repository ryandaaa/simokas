<?php

declare(strict_types=1);

use App\Domains\Inspector\Enums\DocumentType;
use Tests\TestCase;

uses(TestCase::class);

it('has expected document type cases', function () {
    expect(DocumentType::Ktp->value)->toBe('ktp')
        ->and(DocumentType::Certificate->value)->toBe('certificate')
        ->and(DocumentType::Cv->value)->toBe('cv')
        ->and(DocumentType::Portfolio->value)->toBe('portfolio');
});

it('returns non-empty labels for all document types', function (DocumentType $type) {
    expect($type->label())->not->toBeEmpty();
})->with(DocumentType::cases());

it('defines max count per document type correctly', function () {
    expect(DocumentType::Certificate->maxCount())->toBe(3)
        ->and(DocumentType::Ktp->maxCount())->toBe(1)
        ->and(DocumentType::Cv->maxCount())->toBe(1)
        ->and(DocumentType::Portfolio->maxCount())->toBe(1);
});

it('defines maximum file size as 2 MB for all types', function (DocumentType $type) {
    expect($type->maxSizeBytes())->toBe(2 * 1024 * 1024);
})->with(DocumentType::cases());

it('enforces allowed mime types per flow specification', function () {
    expect(DocumentType::Ktp->allowedMimeTypes())->toEqualCanonicalizing([
        'image/jpeg',
        'image/png',
        'application/pdf',
    ]);

    expect(DocumentType::Certificate->allowedMimeTypes())->toEqualCanonicalizing([
        'application/pdf',
        'image/jpeg',
        'image/png',
    ]);

    expect(DocumentType::Cv->allowedMimeTypes())->toEqualCanonicalizing([
        'application/pdf',
    ]);

    expect(DocumentType::Portfolio->allowedMimeTypes())->toEqualCanonicalizing([
        'application/pdf',
        'image/jpeg',
        'image/png',
    ]);
});

it('only allows url for portfolio document type', function () {
    expect(DocumentType::Portfolio->allowsUrl())->toBeTrue()
        ->and(DocumentType::Ktp->allowsUrl())->toBeFalse()
        ->and(DocumentType::Certificate->allowsUrl())->toBeFalse()
        ->and(DocumentType::Cv->allowsUrl())->toBeFalse();
});
