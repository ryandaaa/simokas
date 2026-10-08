<?php

declare(strict_types=1);

use App\Support\Clock;
use Illuminate\Pagination\LengthAwarePaginator;

it('renders x-ui.button with all variants, loading, disabled, and link states', function () {
    // Primary variant (DS: ds-blue solid with white text, pill rounded-full)
    $view = $this->blade('<x-ui.button variant="primary">Kirim</x-ui.button>');
    $view->assertSee('Kirim');
    $view->assertSee('bg-ds-blue');
    $view->assertSee('text-white');
    $view->assertSee('rounded-full');
    $view->assertSee('min-h-[44px]');
    $view->assertSee('wire:loading.attr="disabled"', false);

    // Secondary variant (DS: white with ds-line border and ds-ink text)
    $view = $this->blade('<x-ui.button variant="secondary">Batal</x-ui.button>');
    $view->assertSee('Batal');
    $view->assertSee('bg-white');
    $view->assertSee('border-ds-line');
    $view->assertSee('text-ds-ink');

    // Danger variant (DS: ds-danger solid)
    $view = $this->blade('<x-ui.button variant="danger">Hapus</x-ui.button>');
    $view->assertSee('Hapus');
    $view->assertSee('bg-ds-danger');

    // Ghost variant
    $view = $this->blade('<x-ui.button variant="ghost">Bantuan</x-ui.button>');
    $view->assertSee('Bantuan');
    $view->assertSee('bg-transparent');

    // Disabled state (DS: ds-soft with ds-faint text)
    $view = $this->blade('<x-ui.button :disabled="true">Terkunci</x-ui.button>');
    $view->assertSee('disabled');
    $view->assertSee('bg-ds-soft');
    $view->assertSee('text-ds-faint');

    // Loading state
    $view = $this->blade('<x-ui.button :loading="true">Menyimpan</x-ui.button>');
    $view->assertSee('animate-spin');
    $view->assertSee('disabled');

    // Link state
    $view = $this->blade('<x-ui.button href="/test-url" variant="primary">Lihat</x-ui.button>');
    $view->assertSee('href="/test-url"', false);
    $view->assertSee('<a', false);
});

it('renders x-ui.input with accessibility attributes, hints, errors, and loading state', function () {
    // Basic with label and hint
    $view = $this->blade('<x-ui.input name="email" label="Alamat Email" hint="Gunakan email aktif" />');
    $view->assertSee('Alamat Email');
    $view->assertSee('for="input-email"', false);
    $view->assertSee('id="input-email"', false);
    $view->assertSee('id="input-email-hint"', false);
    $view->assertSee('aria-describedby="input-email-hint"', false);
    $view->assertSee('aria-invalid="false"', false);
    $view->assertSee('min-h-[44px]');

    // Required state
    $view = $this->blade('<x-ui.input name="name" label="Nama" :required="true" />');
    $view->assertSee('required');
    $view->assertSee('aria-required="true"', false);

    // Error state
    $view = $this->blade('<x-ui.input name="phone" label="HP" error="Nomor HP tidak valid" />');
    $view->assertSee('Nomor HP tidak valid');
    $view->assertSee('aria-invalid="true"', false);
    $view->assertSee('border-danger');
    $view->assertSee('role="alert"', false);

    // Loading state
    $view = $this->blade('<x-ui.input name="test" label="Test" :loading="true" />');
    $view->assertSee('animate-pulse');
});

it('renders x-ui.select with options, hint, error, and loading state', function () {
    $options = ['pku' => 'Pekanbaru', 'kpr' => 'Kampar'];

    $view = $this->blade('<x-ui.select name="city" label="Kota" :options="$options" hint="Pilih lokasi" placeholder="Pilih..." />', ['options' => $options]);
    $view->assertSee('Kota');
    $view->assertSee('Pekanbaru');
    $view->assertSee('Kampar');
    $view->assertSee('Pilih...');
    $view->assertSee('aria-describedby="select-city-hint"', false);

    // Error state
    $view = $this->blade('<x-ui.select name="city" label="Kota" error="Kota wajib dipilih" />');
    $view->assertSee('Kota wajib dipilih');
    $view->assertSee('aria-invalid="true"', false);

    // Loading state
    $view = $this->blade('<x-ui.select name="city" label="Kota" :loading="true" />');
    $view->assertSee('animate-pulse');
});

it('renders x-ui.textarea with label, rows, hint, error, and loading state', function () {
    $view = $this->blade('<x-ui.textarea name="desc" label="Deskripsi" :rows="5" hint="Maks 500 kata">Teks awal</x-ui.textarea>');
    $view->assertSee('Deskripsi');
    $view->assertSee('rows="5"', false);
    $view->assertSee('Teks awal');
    $view->assertSee('aria-describedby="textarea-desc-hint"', false);

    // Error state
    $view = $this->blade('<x-ui.textarea name="desc" label="Deskripsi" error="Deskripsi terlalu pendek" />');
    $view->assertSee('Deskripsi terlalu pendek');
    $view->assertSee('aria-invalid="true"', false);

    // Loading state
    $view = $this->blade('<x-ui.textarea name="desc" label="Deskripsi" :loading="true" />');
    $view->assertSee('animate-pulse');
});

it('renders x-ui.checkbox with label, hint, error, and touch area', function () {
    $view = $this->blade('<x-ui.checkbox name="terms" label="Setuju aturan" hint="Wajib dicentang" :checked="true" />');
    $view->assertSee('Setuju aturan');
    $view->assertSee('checked');
    $view->assertSee('min-h-[44px]');
    $view->assertSee('aria-describedby="checkbox-terms-hint"', false);

    // Error state
    $view = $this->blade('<x-ui.checkbox name="terms" label="Setuju" error="Harap centang persetujuan" />');
    $view->assertSee('Harap centang persetujuan');
    $view->assertSee('aria-invalid="true"', false);

    // Loading state
    $view = $this->blade('<x-ui.checkbox name="terms" label="Setuju" :loading="true" />');
    $view->assertSee('animate-pulse');
});

it('renders x-ui.card with header, footer, clickable, and loading state', function () {
    $view = $this->blade('<x-ui.card title="Kartu Uji" subtitle="Subjudul">Konten kartu</x-ui.card>');
    $view->assertSee('Kartu Uji');
    $view->assertSee('Subjudul');
    $view->assertSee('Konten kartu');
    $view->assertSee('rounded-[10px]');
    $view->assertSee('border-fog');

    // Clickable card
    $view = $this->blade('<x-ui.card href="/detail" title="Klik Saya">Isi</x-ui.card>');
    $view->assertSee('href="/detail"', false);
    $view->assertSee('cursor-pointer');

    // Loading state
    $view = $this->blade('<x-ui.card :loading="true">Isi</x-ui.card>');
    $view->assertSee('animate-pulse');
});

it('renders x-ui.listing-card with photo, price, year, mileage, area, and inspected badge', function () {
    $now = Clock::now();

    $view = $this->blade(
        '<x-ui.listing-card title="Honda Beat 2022" :price="14500000" :year="2022" :mileage="15000" area="Pekanbaru" :inspected="true" :inspectedDate="$now" statusLabel="Aktif" statusVariant="success" statusIcon="centang" href="/listing/1" />',
        ['now' => $now]
    );

    $view->assertSee('Honda Beat 2022');
    $view->assertSee('Rp14.500.000');
    $view->assertSee('2022');
    $view->assertSee('15.000 km');
    $view->assertSee('Pekanbaru');
    $view->assertSee('Sudah Diinspeksi');
    $view->assertSee('Aktif');
    $view->assertSee('href="/listing/1"', false);

    // Without photo placeholder
    $view = $this->blade('<x-ui.listing-card title="Mobil Tanpa Foto" :price="50000000" />');
    $view->assertSee('Mobil Tanpa Foto');
    $view->assertSee('Rp50.000.000');

    // Loading state
    $view = $this->blade('<x-ui.listing-card title="Pemuatan" :price="0" :loading="true" />');
    $view->assertSee('animate-pulse');
});

it('renders x-ui.badge-inspected with date and compact formats', function () {
    $now = Clock::now();

    $view = $this->blade('<x-ui.badge-inspected :date="$now" />', ['now' => $now]);
    $view->assertSee('Sudah Diinspeksi');
    $view->assertSee($now->translatedFormat('d M Y'));

    $view = $this->blade('<x-ui.badge-inspected :compact="true" />');
    $view->assertSee('Sudah Diinspeksi');
});

it('renders x-ui.status-chip across all variants and icons', function () {
    $expectedClasses = [
        'success' => 'bg-lime text-forest border-lime',
        'warning' => 'bg-warning/10 text-warning border-warning/30',
        'danger' => 'bg-alarm/10 text-alarm border-alarm/30',
        'primary' => 'bg-linen text-forest border-forest/20',
        'muted' => 'bg-fog text-pebble border-fog',
    ];

    foreach ($expectedClasses as $variant => $classes) {
        $view = $this->blade(
            '<x-ui.status-chip label="Status '.$variant.'" variant="'.$variant.'" icon="centang" />'
        );
        $view->assertSee('Status '.$variant);
        foreach (explode(' ', $classes) as $cls) {
            $view->assertSee($cls);
        }
    }
});

it('renders x-ui.stepper with horizontal and vertical orientations and status indicators', function () {
    $steps = [
        ['label' => 'Diajukan', 'status' => 'completed'],
        ['label' => 'Dibayar', 'status' => 'current'],
        ['label' => 'Inspeksi', 'status' => 'upcoming'],
    ];

    // Horizontal
    $view = $this->blade('<x-ui.stepper :steps="$steps" />', ['steps' => $steps]);
    $view->assertSee('Diajukan');
    $view->assertSee('Dibayar');
    $view->assertSee('Inspeksi');
    $view->assertSee('aria-current="step"', false);
    $view->assertSee('bg-lime');
    $view->assertSee('bg-forest');

    // Vertical
    $view = $this->blade('<x-ui.stepper :steps="$steps" orientation="vertical" />', ['steps' => $steps]);
    $view->assertSee('space-y-4');
    $view->assertSee('Diajukan');
});

it('renders x-ui.empty-state with icon, title, description, and action button', function () {
    $view = $this->blade(
        '<x-ui.empty-state icon="kaca-pembesar" title="Tidak Ditemukan" description="Coba kata kunci lain." actionLabel="Reset" actionUrl="/reset" />'
    );
    $view->assertSee('Tidak Ditemukan');
    $view->assertSee('Coba kata kunci lain.');
    $view->assertSee('Reset');
    $view->assertSee('href="/reset"', false);
});

it('renders x-ui.alert with all variants, retry button, and dismissible state', function () {
    // Info
    $view = $this->blade('<x-ui.alert variant="info" title="Info" message="Pesan informasi" />');
    $view->assertSee('Info');
    $view->assertSee('Pesan informasi');
    $view->assertSee('role="status"', false);

    // Danger with retry
    $view = $this->blade(
        '<x-ui.alert variant="danger" title="Galat" message="Terjadi error" retryLabel="Coba Lagi" retryHref="/retry" />'
    );
    $view->assertSee('Galat');
    $view->assertSee('role="alert"', false);
    $view->assertSee('Coba Lagi');
    $view->assertSee('href="/retry"', false);

    // Dismissible
    $view = $this->blade('<x-ui.alert variant="warning" message="Peringatan" :dismissible="true" />');
    $view->assertSee('x-show="show"', false);
});

it('renders x-ui.modal initially closed with focus trap, alpine listeners, and cloaking', function () {
    $view = $this->blade(
        '<x-ui.modal name="test-modal" title="Konfirmasi Batal" description="Data akan hilang." confirmText="Ya, Batalkan" cancelText="Kembali" />'
    );
    $view->assertSee('Konfirmasi Batal');
    $view->assertSee('Data akan hilang.');
    $view->assertSee('Ya, Batalkan');
    $view->assertSee('Kembali');
    $view->assertSee('role="dialog"', false);
    $view->assertSee('aria-modal="true"', false);

    // Initial state is closed and cloaked
    $view->assertSee('open: false', false);
    $view->assertSee('x-cloak', false);
    $view->assertSee('x-show="open"', false);
    $view->assertSee('x-trap="open"', false);

    // Alpine event listeners and focus handling
    $view->assertSee("x-on:open-modal.window=\"if (\$event.detail === 'test-modal') open = true\"", false);
    $view->assertSee("x-on:close-modal.window=\"if (\$event.detail === 'test-modal') open = false\"", false);
    $view->assertSee('x-on:keydown.escape.window="open = false"', false);
    $view->assertSee('this.triggerEl = document.activeElement;', false);
    $view->assertSee('this.triggerEl.focus()', false);

    // Close actions
    $view->assertSee('@click="open = false"', false);
    $view->assertSee('@click.outside="open = false"', false);
});

it('renders x-ui.money using Support\Money formatting', function () {
    $view = $this->blade('<x-ui.money :amount="50000" />');
    $view->assertSee('Rp50.000');

    $view = $this->blade('<x-ui.money :amount="15000" />');
    $view->assertSee('Rp15.000');

    $view = $this->blade('<x-ui.money :amount="125000000" />');
    $view->assertSee('Rp125.000.000');
});

it('renders x-ui.datetime using Support\Clock WIB formatting', function () {
    $time = Clock::toWib('2026-10-05 07:30:00');

    $view = $this->blade('<x-ui.datetime :value="$time" />', ['time' => $time]);
    $view->assertSee('5 Okt 2026, 14.30 WIB');

    $view = $this->blade('<x-ui.datetime :value="$time" format="date" />', ['time' => $time]);
    $view->assertSee('5 Okt 2026');

    $view = $this->blade('<x-ui.datetime :value="$time" format="time" />', ['time' => $time]);
    $view->assertSee('14.30 WIB');
});

it('renders x-ui.score-ring with circular score and recommendation chip', function () {
    // Recommended
    $view = $this->blade('<x-ui.score-ring :score="88" recommendation="recommended" />');
    $view->assertSee('88');
    $view->assertSee('Layak');
    $view->assertSee('text-forest');

    // Caution
    $view = $this->blade('<x-ui.score-ring :score="65" recommendation="caution" />');
    $view->assertSee('65');
    $view->assertSee('Perlu Perhatian');
    $view->assertSee('text-warning');

    // Not Recommended
    $view = $this->blade('<x-ui.score-ring :score="40" recommendation="not_recommended" />');
    $view->assertSee('40');
    $view->assertSee('Tidak Direkomendasikan');
    $view->assertSee('text-alarm');

    // Loading
    $view = $this->blade('<x-ui.score-ring :loading="true" />');
    $view->assertSee('animate-pulse');
});

it('renders x-ui.camera-capture with ready, preview, and permission denied states', function () {
    $now = Clock::now();

    // Ready state
    $view = $this->blade('<x-ui.camera-capture label="Foto Depan" />');
    $view->assertSee('Foto Depan');
    $view->assertSee('Ambil Foto');
    $view->assertSee('capture="environment"', false);

    // Preview state
    $view = $this->blade(
        '<x-ui.camera-capture label="Foto Depan" photoUrl="/photo.jpg" jobId="JOB-123" :timestamp="$now" />',
        ['now' => $now]
    );
    $view->assertSee('ID Job: JOB-123');
    $view->assertSee('Ulangi');
    $view->assertSee('Pakai foto ini');
    $view->assertSee('/photo.jpg');

    // Permission denied state
    $view = $this->blade('<x-ui.camera-capture label="Foto Depan" :permissionDenied="true" />');
    $view->assertSee('Izin kamera ditolak');

    // Loading state
    $view = $this->blade('<x-ui.camera-capture :loading="true" />');
    $view->assertSee('animate-pulse');
});

it('renders x-ui.pagination with page navigation and contract support', function () {
    // Primitive props
    $view = $this->blade('<x-ui.pagination :currentPage="2" :totalPages="5" :total="60" previousUrl="/p/1" nextUrl="/p/3" />');
    $view->assertSee('Halaman Sebelumnya');
    $view->assertSee('Halaman Berikutnya');
    $view->assertSee('href="/p/1"', false);
    $view->assertSee('href="/p/3"', false);
    $view->assertSee('60');

    // LengthAwarePaginator
    $paginator = new LengthAwarePaginator(
        items: range(1, 12),
        total: 36,
        perPage: 12,
        currentPage: 1
    );

    $view = $this->blade('<x-ui.pagination :paginator="$paginator" />', ['paginator' => $paginator]);
    $view->assertSee('Halaman');
});

it('renders x-ui.skeleton for various variants', function () {
    $variants = ['text', 'title', 'card', 'avatar', 'button', 'image', 'badge', 'circle'];

    foreach ($variants as $variant) {
        $view = $this->blade('<x-ui.skeleton variant="'.$variant.'" />');
        $view->assertSee('animate-pulse');
        $view->assertSee('bg-fog');
    }
});

it('renders x-ui.icon with outline and solid variants for all required status and general icons', function () {
    $icons = [
        'jam', 'centang', 'kaca-pembesar', 'dua-panah', 'tag', 'silang',
        'jeda', 'jam-pasir', 'orang', 'kalender', 'dokumen', 'segitiga-peringatan',
        'kamera', 'perisai-centang', 'panah-putar', 'photo', 'home', 'bell',
        'chevron-left', 'chevron-right', 'check-circle', 'x-circle', 'information-circle',
    ];

    foreach ($icons as $icon) {
        $viewOutline = $this->blade('<x-ui.icon name="'.$icon.'" variant="outline" />');
        $viewOutline->assertSee('<svg', false);
        $viewOutline->assertSee('fill="none"', false);

        $viewSolid = $this->blade('<x-ui.icon name="'.$icon.'" variant="solid" />');
        $viewSolid->assertSee('<svg', false);
        $viewSolid->assertSee('fill="currentColor"', false);
    }
});

it('renders x-ui.button with DS color rules and pill shape', function () {
    // Primary action button is ds-blue solid with white text
    $viewPri = $this->blade('<x-ui.button variant="primary">Aksi Utama</x-ui.button>');
    $viewPri->assertSee('bg-ds-blue');
    $viewPri->assertSee('text-white');
    $viewPri->assertSee('rounded-full');

    // Secondary button is white with ds-line border
    $viewSec = $this->blade('<x-ui.button variant="secondary">Aksi Kedua</x-ui.button>');
    $viewSec->assertSee('bg-white');
    $viewSec->assertSee('border-ds-line');
    $viewSec->assertSee('hover:text-ds-blue');

    // Disabled button is ds-soft with ds-faint text
    $viewDis = $this->blade('<x-ui.button variant="primary" :disabled="true">Nonaktif</x-ui.button>');
    $viewDis->assertSee('bg-ds-soft');
    $viewDis->assertSee('text-ds-faint');
});

it('renders x-ui.status-chip with Wise theme active lime and primary linen', function () {
    // Active / success status is lime with forest text
    $viewActive = $this->blade('<x-ui.status-chip label="Aktif" variant="success" icon="centang" />');
    $viewActive->assertSee('bg-lime');
    $viewActive->assertSee('text-forest');

    // Primary status is linen with forest text
    $viewPri = $this->blade('<x-ui.status-chip label="Diproses" variant="primary" icon="jam" />');
    $viewPri->assertSee('bg-linen');
    $viewPri->assertSee('text-forest');

    // Danger status is alarm
    $viewDanger = $this->blade('<x-ui.status-chip label="Ditolak" variant="danger" icon="silang" />');
    $viewDanger->assertSee('bg-alarm/10');
    $viewDanger->assertSee('text-alarm');

    // Muted status is fog
    $viewMuted = $this->blade('<x-ui.status-chip label="Terjual" variant="muted" icon="tag" />');
    $viewMuted->assertSee('bg-fog');
    $viewMuted->assertSee('text-pebble');
});
