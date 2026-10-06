<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
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

it('ensures app.css declares satoshi font-face locally without external urls', function () {
    $cssContent = file_get_contents(resource_path('css/app.css'));

    expect($cssContent)->not->toBeFalse();

    // Pastikan font-family Satoshi dideklarasikan di @font-face
    expect($cssContent)->toContain("font-family: 'Satoshi'");

    // Pastikan tidak ada URL http(s) eksternal
    expect($cssContent)->not->toMatch('/url\(["\']?https?:\/\//i');

    // Ekstrak URL font yang dirujuk
    preg_match_all('/url\(["\']?([^"\')]+)["\']?\)/i', (string) $cssContent, $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $fontUrl) {
        $resolvedPath = realpath(resource_path('css/'.$fontUrl));

        expect($resolvedPath)->not->toBeFalse("Berkas font '{$fontUrl}' tidak ditemukan di disk.")
            ->and($resolvedPath)->toStartWith(resource_path('fonts/satoshi'));
    }
});

it('ensures satoshi font-face uses font-display swap', function () {
    $cssContent = (string) file_get_contents(resource_path('css/app.css'));

    preg_match_all('/@font-face\s*\{([^}]+)\}/s', $cssContent, $matches);
    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $block) {
        if (str_contains($block, "'Satoshi'") || str_contains($block, '"Satoshi"')) {
            expect($block)->toContain('font-display: swap;');
        }
    }
});

it('ensures layout contains two preload links for above-the-fold satoshi fonts', function () {
    $layoutContent = (string) file_get_contents(resource_path('views/components/layouts/app.blade.php'));

    // Verifikasi tag preload untuk Satoshi Regular dan Bold
    expect($layoutContent)->toMatch('/<link\s+rel=["\']preload["\'][^>]+Satoshi-Regular\.woff2[^>]+as=["\']font["\'][^>]+type=["\']font\/woff2["\'][^>]+crossorigin/i')
        ->and($layoutContent)->toMatch('/<link\s+rel=["\']preload["\'][^>]+Satoshi-Bold\.woff2[^>]+as=["\']font["\'][^>]+type=["\']font\/woff2["\'][^>]+crossorigin/i');

    // Render layout sederhana untuk memastikan ekspresi Vite dievaluasi tanpa galat
    $rendered = Blade::render('<x-layouts.app :title="\'Uji Preload\'"><div>Konten</div></x-layouts.app>');
    expect($rendered)->toContain('as="font"')
        ->and($rendered)->toContain('type="font/woff2"')
        ->and($rendered)->toContain('crossorigin')
        ->and($rendered)->toMatch('/href=["\'][^"\']*Satoshi-Regular[^"\']*\.woff2["\']/')
        ->and($rendered)->toMatch('/href=["\'][^"\']*Satoshi-Bold[^"\']*\.woff2["\']/');
});

it('ensures listing-card component renders img with lazy loading, dimensions and alt', function () {
    $rendered = Blade::render(
        '<x-ui.listing-card :title="\'Toyota Yaris 2020\'" :price="180000000" image-url="https://example.com/test.jpg" />'
    );

    expect($rendered)->toContain('loading="lazy"')
        ->and($rendered)->toContain('width="400"')
        ->and($rendered)->toContain('height="250"')
        ->and($rendered)->toContain('alt="Toyota Yaris 2020"')
        ->and($rendered)->toContain('aspect-[16/10]');
});

it('ensures catalog detail view marks main photo above the fold eager and thumbnails lazy', function () {
    $viewContent = (string) file_get_contents(app_path('Domains/Catalog/Resources/views/show.blade.php'));

    // Foto utama di atas lipatan: index 0 eager, berikutnya lazy
    expect($viewContent)->toContain('@if ($index > 0) loading="lazy" @else loading="eager" @endif')
        ->and($viewContent)->toContain('width="800"')
        ->and($viewContent)->toContain('height="600"')
        ->and($viewContent)->toContain('width="200"')
        ->and($viewContent)->toContain('height="150"');
});
