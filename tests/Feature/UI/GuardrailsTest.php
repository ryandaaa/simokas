<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('ensures no hex color codes or style attributes exist in blade views', function () {
    $directories = [
        resource_path('views'),
    ];

    $domainViews = glob(app_path('Domains/*/Resources/views'));
    if ($domainViews) {
        $directories = array_merge($directories, $domainViews);
    }

    $finder = (new Finder)
        ->files()
        ->in($directories)
        ->name('*.blade.php');

    $violations = [];

    foreach ($finder as $file) {
        $content = $file->getContents();
        $filePath = $file->getRelativePathname();

        // 1. Periksa atribut style="..." atau style='...'
        if (preg_match_all('/style\s*=\s*["\'][^"\']*["\']/i', $content, $styleMatches, PREG_OFFSET_CAPTURE)) {
            foreach ($styleMatches[0] as $match) {
                $violations[] = "{$filePath}: inline style ditemukan: '{$match[0]}'";
            }
        }

        // 2. Periksa kode warna hex (#xxx atau #xxxxxx), abaikan anchor link seperti href="#..." atau id="..."
        $lines = explode("\n", $content);
        foreach ($lines as $lineNum => $line) {
            // Hapus anchor link href="#..." dan id="#..." dari pemeriksaan baris
            $sanitizedLine = preg_replace('/(href|id)\s*=\s*["\']#[^"\']*["\']/i', '', $line);
            if ($sanitizedLine === null) {
                $sanitizedLine = $line;
            }

            if (preg_match('/#([0-9a-fA-F]{3,8})\b/', $sanitizedLine, $hexMatches)) {
                $violations[] = "{$filePath}:".($lineNum + 1)." kode hex ditemukan: '{$hexMatches[0]}'";
            }
        }
    }

    expect($violations)->toBeEmpty(
        "Ditemukan pelanggaran aturan token/style di Blade view:\n".implode("\n", $violations)
    );
});

it('ensures domain views do not use raw tailwind color or typography classes outside x-ui', function () {
    $domainViews = glob(app_path('Domains/*/Resources/views'));
    if (! $domainViews) {
        expect(true)->toBeTrue();

        return;
    }

    $finder = (new Finder)
        ->files()
        ->in($domainViews)
        ->name('*.blade.php');

    $violations = [];

    // Pola warna bawaan Tailwind mentah (contoh: bg-red-500, text-gray-700, bg-blue-600)
    // Domain view harus memakai token (--color-primary, dsb) atau komponen x-ui
    $rawColorPattern = '/\b(?:bg|text|border|ring)-(?:slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-(?:50|100|200|300|400|500|600|700|800|900|950)\b/';

    foreach ($finder as $file) {
        $content = $file->getContents();
        $filePath = $file->getPathname();

        if (preg_match_all($rawColorPattern, $content, $matches)) {
            $violations[] = "{$filePath} menggunakan kelas warna Tailwind mentah: ".implode(', ', array_unique($matches[0]));
        }
    }

    expect($violations)->toBeEmpty(
        "Domain view dilarang menggunakan kelas warna Tailwind mentah:\n".implode("\n", $violations)
    );
});

it('ensures app.css defines x-cloak rule with display none important', function () {
    $cssContent = file_get_contents(resource_path('css/app.css'));

    expect($cssContent)->not->toBeFalse()
        ->and($cssContent)->toContain('[x-cloak]')
        ->and($cssContent)->toMatch('/\[x-cloak\]\s*\{\s*display:\s*none\s*!important;\s*\}/');
});
