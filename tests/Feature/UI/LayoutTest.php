<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

    // Menu bawah di HP: selalu tampil mengikuti role (tamu di sini, tanpa navItems pun tetap ada)
    $view->assertSee('aria-label="Navigasi Seluler"', false);
    $view->assertSee('Beranda');
    $view->assertSee('Kendaraan');
    $view->assertSee('Bantuan');
    $view->assertSee('Masuk');
    $view->assertSee('Jual');

    // aria-live region untuk notifikasi
    $view->assertSee('id="notifications-live-region"', false);
    $view->assertSee('aria-live="polite"', false);
    $view->assertSee('Pemberitahuan penting');

    // Footer dan tautan hukum
    $view->assertSee(route('legal.privacy'), false);
    $view->assertSee(route('legal.terms'), false);
    $view->assertSee(__('legal.footer_privacy'));
    $view->assertSee(__('legal.footer_terms'));
});

it('shows role-based bottom nav for logged-in users without navItems', function () {
    $user = User::factory()->user()->make();

    $view = $this->actingAs($user)->blade('<x-layouts.app title="Uji Nav Bawah"><p>Konten</p></x-layouts.app>');

    $view->assertSee('aria-label="Navigasi Seluler"', false);
    $view->assertSee('Beranda');
    $view->assertSee('Iklan Saya');
    $view->assertSee('Transaksi');
    $view->assertSee('Percakapan');
    $view->assertSee('Jual');
    $view->assertSee(route('listings.mine'), false);
    $view->assertSee(route('transactions.index'), false);
});

it('shows slim footer to logged-in users and full footer to guests', function () {
    $user = User::factory()->active()->create();

    $this->actingAs($user);
    $authed = (string) $this->blade('<x-layouts.app title="Uji Footer"><p>Konten</p></x-layouts.app>');

    expect($authed)->not->toContain('NAVIGASI')
        ->and($authed)->toContain(__('legal.footer_terms'))
        ->and($authed)->toContain(__('legal.footer_privacy'));

    auth()->logout();
    $guest = (string) $this->blade('<x-layouts.app title="Uji Footer"><p>Konten</p></x-layouts.app>');

    expect($guest)->toContain('NAVIGASI');
});

it('shows full footer on home page even when logged in', function () {
    $user = User::factory()->active()->create();

    $this->actingAs($user);
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('NAVIGASI', false);
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

it('shows account menu with Keluar for logged-in users', function () {
    $user = User::factory()->make(['name' => 'Pengguna Menu Akun']);

    $view = $this->actingAs($user)->blade('<x-layouts.app title="Uji Menu"><p>Konten</p></x-layouts.app>');

    $view->assertSee('Pengguna Menu Akun');
    $view->assertSee('hidden sm:inline max-w-28 truncate', false);
    $view->assertSee(__('ui.layout.account_menu'));
    $view->assertSee(__('ui.layout.dashboard'));
    $view->assertSee(route('dashboard'), false);
    $view->assertSee(route('identity.logout'), false);
    $view->assertSee('Keluar');
    $view->assertSee('method="POST"', false);
});

it('does not show account menu or Keluar for guests', function () {
    $view = $this->blade('<x-layouts.app title="Uji Menu"><p>Konten</p></x-layouts.app>');

    $view->assertDontSee('Keluar');
    $view->assertDontSee(__('ui.layout.account_menu'));
    $view->assertSee(__('Masuk'));
});

it('renders role-based navigation links for regular users and hides other role links', function () {
    $user = User::factory()->user()->create([
        'name' => 'Budi Pembeli',
        'email' => 'budi.user@example.test',
    ]);

    $view = $this->actingAs($user)->blade('<x-layouts.app title="Uji Menu User"><p>Konten</p></x-layouts.app>');

    // User links
    $view->assertSee(__('ui.layout.dashboard'));
    $view->assertSee(route('dashboard'), false);
    $view->assertSee(__('ui.layout.my_listings'));
    $view->assertSee(route('listings.mine'), false);
    $view->assertSee(__('ui.layout.create_listing'));
    $view->assertSee(route('listings.create'), false);
    $view->assertSee(__('ui.layout.my_inspections'));
    $view->assertSee(route('jobs.mine'), false);
    $view->assertSee(__('ui.layout.transactions'));
    $view->assertSee(route('transactions.index'), false);
    $view->assertSee(__('ui.layout.conversations'));
    $view->assertSee(route('conversations.index'), false);
    $view->assertSee(__('ui.layout.notifications'));
    $view->assertSee(route('notifications.index'), false);

    // Other role links hidden from user
    $view->assertDontSee(__('ui.layout.queue'));
    $view->assertDontSee(route('admin.queue.index'), false);
    $view->assertDontSee(__('ui.layout.open_jobs'));
    $view->assertDontSee(route('jobs.open'), false);
    $view->assertDontSee(__('ui.layout.my_jobs'));
    $view->assertDontSee(route('jobs.inspector'), false);
    $view->assertDontSee(__('ui.layout.earnings'));
    $view->assertDontSee(route('inspector.earnings'), false);
    $view->assertDontSee(__('ui.layout.onboarding'));
    $view->assertDontSee(route('inspector.onboarding'), false);
    $view->assertDontSee(__('ui.layout.audit_logs'));
    $view->assertDontSee(route('admin.audit-logs.index'), false);
});

it('renders role-based navigation links for approved inspectors and hides other role links', function () {
    $inspector = User::factory()->inspector()->create([
        'name' => 'Eko Inspektor',
        'email' => 'eko.inspector@example.test',
    ]);

    InspectorProfile::factory()->approved()->create([
        'user_id' => $inspector->id,
    ]);

    $view = $this->actingAs($inspector)->blade('<x-layouts.app title="Uji Menu Inspektor"><p>Konten</p></x-layouts.app>');

    // Approved inspector links
    $view->assertSee(__('ui.layout.dashboard'));
    $view->assertSee(route('dashboard'), false);
    $view->assertSee(__('ui.layout.open_jobs'));
    $view->assertSee(route('jobs.open'), false);
    $view->assertSee(__('ui.layout.my_jobs'));
    $view->assertSee(route('jobs.inspector'), false);
    $view->assertSee(__('ui.layout.earnings'));
    $view->assertSee(route('inspector.earnings'), false);
    $view->assertSee(__('ui.layout.conversations'));
    $view->assertSee(route('conversations.index'), false);
    $view->assertSee(__('ui.layout.notifications'));
    $view->assertSee(route('notifications.index'), false);

    // Other role links hidden from approved inspector
    $view->assertDontSee(__('ui.layout.onboarding'));
    $view->assertDontSee(route('inspector.onboarding'), false);
    $view->assertDontSee(__('ui.layout.queue'));
    $view->assertDontSee(route('admin.queue.index'), false);
    $view->assertDontSee(__('ui.layout.my_listings'));
    $view->assertDontSee(route('listings.mine'), false);
    $view->assertDontSee(__('ui.layout.create_listing'));
    // Pengecualian disengaja: FAB "Jual" di nav bawah HP tampil untuk semua role.
    $view->assertSee('aria-label="Pasang Iklan"', false);
    $view->assertDontSee(__('ui.layout.my_inspections'));
    $view->assertDontSee(route('jobs.mine'), false);
    $view->assertDontSee(__('ui.layout.audit_logs'));
    $view->assertDontSee(route('admin.audit-logs.index'), false);
});

it('renders only Dashboard and Onboarding for unapproved inspectors without job links', function () {
    $unapprovedInspector = User::factory()->inspector()->create([
        'name' => 'Doni Calon Inspektor',
        'email' => 'doni.unapproved@example.test',
    ]);

    InspectorProfile::factory()->pending()->create([
        'user_id' => $unapprovedInspector->id,
    ]);

    $view = $this->actingAs($unapprovedInspector)->blade('<x-layouts.app title="Uji Menu Unapproved"><p>Konten</p></x-layouts.app>');

    // Allowed links
    $view->assertSee(__('ui.layout.dashboard'));
    $view->assertSee(route('dashboard'), false);
    $view->assertSee(__('ui.layout.onboarding'));
    $view->assertSee(route('inspector.onboarding'), false);

    // Job and other links must NOT be visible
    $view->assertDontSee(__('ui.layout.open_jobs'));
    $view->assertDontSee(route('jobs.open'), false);
    $view->assertDontSee(__('ui.layout.my_jobs'));
    $view->assertDontSee(route('jobs.inspector'), false);
    $view->assertDontSee(__('ui.layout.earnings'));
    $view->assertDontSee(route('inspector.earnings'), false);
    $view->assertDontSee(__('ui.layout.conversations'));
    $view->assertDontSee(route('conversations.index'), false);
    // Pengecualian disengaja: "Notifikasi" ada di nav bawah HP untuk semua role.
    $view->assertDontSee(__('ui.layout.queue'));
    $view->assertDontSee(route('admin.queue.index'), false);
    $view->assertDontSee(__('ui.layout.my_listings'));
    $view->assertDontSee(route('listings.mine'), false);
});

it('renders role-based navigation links for admin and hides other role links', function () {
    $admin = User::factory()->admin()->create([
        'name' => 'Siti Admin',
        'email' => 'siti.admin@example.test',
    ]);

    $view = $this->actingAs($admin)->blade('<x-layouts.app title="Uji Menu Admin"><p>Konten</p></x-layouts.app>');

    // Admin links
    $view->assertSee(__('ui.layout.dashboard'));
    $view->assertSee(route('dashboard'), false);
    $view->assertSee(__('ui.layout.queue'));
    $view->assertSee(route('admin.queue.index'), false);
    $view->assertSee(__('ui.layout.admin_jobs'));
    $view->assertSee(route('admin.jobs.index'), false);
    $view->assertSee(__('ui.layout.inspectors'));
    $view->assertSee(route('admin.inspectors.index'), false);
    $view->assertSee(__('ui.layout.admin_listings'));
    $view->assertSee(route('admin.listings.index'), false);
    $view->assertSee(__('ui.layout.templates'));
    $view->assertSee(route('admin.templates.index'), false);
    $view->assertSee(__('ui.layout.settings'));
    $view->assertSee(route('admin.settings'), false);
    $view->assertSee(__('ui.layout.audit_logs'));
    $view->assertSee(route('admin.audit-logs.index'), false);

    // Other role links hidden from admin
    $view->assertDontSee(__('ui.layout.open_jobs'));
    $view->assertDontSee(route('jobs.open'), false);
    $view->assertDontSee(__('ui.layout.my_jobs'));
    $view->assertDontSee(route('jobs.inspector'), false);
    $view->assertDontSee(__('ui.layout.earnings'));
    $view->assertDontSee(route('inspector.earnings'), false);
    $view->assertDontSee(__('ui.layout.onboarding'));
    $view->assertDontSee(route('inspector.onboarding'), false);
    $view->assertDontSee(__('ui.layout.my_inspections'));
    $view->assertDontSee(route('jobs.mine'), false);
    $view->assertDontSee(__('ui.layout.my_listings'));
    $view->assertDontSee(route('listings.mine'), false);
    $view->assertDontSee(__('ui.layout.create_listing'));
    // Pengecualian disengaja: FAB "Jual" di nav bawah HP tampil untuk semua role.
    $view->assertSee('aria-label="Pasang Iklan"', false);
});

it('marks active role navigation link with active styling and aria-current page', function () {
    $user = User::factory()->user()->create([
        'name' => 'Budi Pembeli',
        'email' => 'budi.active@example.test',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk();

    $content = $response->getContent();
    expect($content)->toContain('aria-current="page"')
        ->and($content)->toContain('bg-ds-blue-soft text-ds-blue-ink font-bold');
});

it('renders floating accessibility widget on every page', function () {
    $view = $this->blade('<x-layouts.app title="Uji A11y"><p>Konten</p></x-layouts.app>');

    $view->assertSee(__('ui.layout.a11y_button'));
    $view->assertSee(__('ui.layout.a11y_title'));
    $view->assertSee('simokas-a11y', false);
    $view->assertSee('fa-universal-access', false);
});

it('renders responsive navbar logo with mobile and desktop assets', function () {
    $view = $this->blade('<x-layouts.app title="Uji Logo"><p>Konten</p></x-layouts.app>');

    $view->assertSee(asset('images/logo-simokas-mobile.png'), false);
    $view->assertSee(asset('images/logo-simokas.png'), false);
    $view->assertSee('sm:hidden', false);
    $view->assertSee('hidden sm:block', false);
});
