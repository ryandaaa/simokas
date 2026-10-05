<?php

declare(strict_types=1);

it('renders application layout with desktop navbar, mobile bottom bar, skip-link, and aria-live region', function () {
    $navItems = [
        ['label' => 'Beranda', 'url' => '/', 'icon' => 'rumah', 'active' => true],
        ['label' => 'Cari', 'url' => '/cari', 'icon' => 'kaca-pembesar', 'active' => false],
        ['label' => 'Pekerjaan', 'url' => '/pekerjaan', 'icon' => 'dokumen', 'active' => false],
        ['label' => 'Transaksi', 'url' => '/transaksi', 'icon' => 'dua-panah', 'active' => false],
        ['label' => 'Profil', 'url' => '/profil', 'icon' => 'orang', 'active' => false],
        ['label' => 'Item Keenam', 'url' => '/ekstra', 'icon' => 'tag', 'active' => false], // Harus dipotong maks 5 di HP
    ];

    $view = $this->blade(
        '<x-layouts.app title="Halaman Uji" :navItems="$navItems">
            <x-slot:notifications>
                <div id="test-alert">Pemberitahuan penting</div>
            </x-slot:notifications>
            <p>Konten Utama Halaman</p>
        </x-layouts.app>',
        ['navItems' => $navItems]
    );

    // Judul dan skip-link
    $view->assertSee('Halaman Uji — SIMOKAS');
    $view->assertSee('Lewati ke konten utama');
    $view->assertSee('href="#main-content"', false);

    // Konten utama
    $view->assertSee('id="main-content"', false);
    $view->assertSee('Konten Utama Halaman');

    // Bilah atas desktop
    $view->assertSee('aria-label="Navigasi Utama"', false);
    $view->assertSee('Beranda');
    $view->assertSee('Cari');

    // Menu bawah di HP (hanya maks 5 item pertama)
    $view->assertSee('aria-label="Navigasi Seluler"', false);
    $view->assertSee('Profil');

    // aria-live region untuk notifikasi
    $view->assertSee('id="notifications-live-region"', false);
    $view->assertSee('aria-live="polite"', false);
    $view->assertSee('Pemberitahuan penting');
});

it('loads livewire and alpine scripts and styles exactly once in application layout', function () {
    $view = $this->blade('<x-layouts.app title="Uji Script"><p>Konten</p></x-layouts.app>');

    $content = (string) $view;

    // Memuat style Livewire
    expect($content)->toContain('wire\:loading');

    // Memuat script Livewire/Alpine tepat satu kali
    expect(substr_count($content, 'livewire.js'))->toBe(1);
});

it('loads livewire and alpine scripts exactly once on gallery page', function () {
    $responseGallery = $this->get('/_ui');
    $responseGallery->assertStatus(200);
    $galleryContent = $responseGallery->getContent();

    expect(substr_count($galleryContent, 'livewire.js'))->toBe(1)
        ->and($galleryContent)->toContain('wire\:loading');
});

it('loads livewire and alpine scripts exactly once on 404 error page', function () {
    $response404 = $this->get('/non-existent-route-for-404-test');
    $response404->assertStatus(404);
    $errorContent = $response404->getContent();

    expect(substr_count($errorContent, 'livewire.js'))->toBe(1)
        ->and($errorContent)->toContain('wire\:loading');
});
