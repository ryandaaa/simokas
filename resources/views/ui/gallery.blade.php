@php
    $galleryNavItems = [
        ['label' => 'Galeri UI', 'url' => url('/_ui'), 'icon' => 'dokumen', 'active' => true],
        ['label' => 'Beranda', 'url' => url('/'), 'icon' => 'rumah', 'active' => false],
        ['label' => 'Cari', 'url' => '#', 'icon' => 'kaca-pembesar', 'active' => false],
        ['label' => 'Notifikasi', 'url' => '#', 'icon' => 'lonceng', 'active' => false],
        ['label' => 'Profil', 'url' => '#', 'icon' => 'orang', 'active' => false],
    ];

    $sampleSteps = [
        ['label' => 'Diajukan', 'description' => 'Menunggu verifikasi admin', 'status' => 'completed'],
        ['label' => 'Dibayar', 'description' => 'Pembayaran simulasi berhasil', 'status' => 'completed'],
        ['label' => 'Inspeksi', 'description' => 'Inspektor sedang memeriksa', 'status' => 'current'],
        ['label' => 'Selesai', 'description' => 'Laporan siap diakses', 'status' => 'upcoming'],
    ];

    $now = \App\Support\Clock::now();
@endphp

<x-layouts.app title="Galeri Komponen UI" :navItems="$galleryNavItems">
    <div class="space-y-12 pb-16">
        {{-- Header Galeri --}}
        <div class="border-b border-muted/20 pb-6">
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 rounded-md bg-primary text-surface text-xs font-bold uppercase tracking-wider">
                    Internal
                </span>
                <span class="text-xs text-muted">Hanya tersedia di local / testing</span>
            </div>
            <h1 class="text-3xl font-extrabold text-text mt-2">Galeri Komponen UI SIMOKAS</h1>
            <p class="text-base text-muted mt-1 max-w-3xl">
                Dokumentasi kontrak API, varian, dan keadaan seluruh komponen bersama (<code class="text-primary font-mono text-sm">&lt;x-ui.*&gt;</code>) dan token desain Tailwind CSS v4. Pendekatan F2 polos: markup semantik, rapi, mobile-first, tanpa dekorasi ekstra.
            </p>
        </div>

        {{-- 1. Token Desain --}}
        <section id="tokens" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-obs">1. Token Desain</h2>
                <p class="text-sm text-slate">Didefinisikan di <code class="font-mono text-xs">resources/css/app.css</code> (@theme). Dilarang memakai hex atau style inline di view.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                <div class="p-3 rounded-[10px] bg-surface border border-fog space-y-2">
                    <div class="h-10 w-full rounded-full bg-forest"></div>
                    <div>
                        <p class="text-xs font-bold text-obs">--color-forest</p>
                        <p class="text-[11px] text-slate">Teks utama, aksen gelap</p>
                    </div>
                </div>

                <div class="p-3 rounded-[10px] bg-surface border border-fog space-y-2">
                    <div class="h-10 w-full rounded-full bg-lime border border-forest/10"></div>
                    <div>
                        <p class="text-xs font-bold text-obs">--color-lime</p>
                        <p class="text-[11px] text-slate">Aksi primer, status aktif</p>
                    </div>
                </div>

                <div class="p-3 rounded-[10px] bg-surface border border-fog space-y-2">
                    <div class="h-10 w-full rounded-full bg-spruce"></div>
                    <div>
                        <p class="text-xs font-bold text-obs">--color-spruce</p>
                        <p class="text-[11px] text-slate">Hover tautan</p>
                    </div>
                </div>

                <div class="p-3 rounded-[10px] bg-surface border border-fog space-y-2">
                    <div class="h-10 w-full rounded-full bg-linen border border-forest/10"></div>
                    <div>
                        <p class="text-xs font-bold text-obs">--color-linen</p>
                        <p class="text-[11px] text-slate">Hover tombol sekunder</p>
                    </div>
                </div>

                <div class="p-3 rounded-[10px] bg-surface border border-fog space-y-2">
                    <div class="h-10 w-full rounded-full bg-signal"></div>
                    <div>
                        <p class="text-xs font-bold text-obs">--color-signal</p>
                        <p class="text-[11px] text-slate">Informasi</p>
                    </div>
                </div>

                <div class="p-3 rounded-[10px] bg-surface border border-fog space-y-2">
                    <div class="h-10 w-full rounded-full bg-alarm"></div>
                    <div>
                        <p class="text-xs font-bold text-obs">--color-alarm</p>
                        <p class="text-[11px] text-slate">Galat, bahaya</p>
                    </div>
                </div>

                <div class="p-3 rounded-[10px] bg-surface border border-fog space-y-2">
                    <div class="h-10 w-full rounded-full bg-charcoal"></div>
                    <div>
                        <p class="text-xs font-bold text-obs">--color-charcoal</p>
                        <p class="text-[11px] text-slate">Teks isi</p>
                    </div>
                </div>

                <div class="p-3 rounded-[10px] bg-surface border border-fog space-y-2">
                    <div class="h-10 w-full rounded-full bg-obs"></div>
                    <div>
                        <p class="text-xs font-bold text-obs">--color-obs</p>
                        <p class="text-[11px] text-slate">Judul</p>
                    </div>
                </div>

                <div class="p-3 rounded-[10px] bg-surface border border-fog space-y-2">
                    <div class="h-10 w-full rounded-full bg-pebble"></div>
                    <div>
                        <p class="text-xs font-bold text-obs">--color-pebble</p>
                        <p class="text-[11px] text-slate">Border input, placeholder</p>
                    </div>
                </div>

                <div class="p-3 rounded-[10px] bg-surface border border-fog space-y-2">
                    <div class="h-10 w-full rounded-full bg-slate"></div>
                    <div>
                        <p class="text-xs font-bold text-obs">--color-slate</p>
                        <p class="text-[11px] text-slate">Teks sekunder</p>
                    </div>
                </div>

                <div class="p-3 rounded-[10px] bg-surface border border-fog space-y-2">
                    <div class="h-10 w-full rounded-full bg-fog border border-pebble/20"></div>
                    <div>
                        <p class="text-xs font-bold text-obs">--color-fog</p>
                        <p class="text-[11px] text-slate">Garis halus, latar nonaktif</p>
                    </div>
                </div>

                <div class="p-3 rounded-[10px] bg-surface border border-fog space-y-2">
                    <div class="h-10 w-full rounded-full bg-paper border border-fog"></div>
                    <div>
                        <p class="text-xs font-bold text-obs">--color-paper</p>
                        <p class="text-[11px] text-slate">Kanvas putih</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- 2. Ikon Heroicons Inline --}}
        <section id="icons" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-text">2. Komponen Ikon (&lt;x-ui.icon&gt;)</h2>
                <p class="text-sm text-muted">Props: <code class="font-mono text-xs">name</code> (string), <code class="font-mono text-xs">variant</code> ('outline' | 'solid', bawaan 'outline'). SVG inline Heroicons tanpa paket eksternal.</p>
            </div>

            <x-ui.card>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-4">
                    @php
                        $sampleIcons = [
                            'jam', 'centang', 'kaca-pembesar', 'dua-panah', 'tag', 'silang',
                            'jeda', 'jam-pasir', 'orang', 'kalender', 'dokumen', 'segitiga-peringatan',
                            'kamera', 'perisai-centang', 'panah-putar', 'foto', 'rumah', 'lonceng'
                        ];
                    @endphp
                    @foreach ($sampleIcons as $iconName)
                        <div class="flex flex-col items-center justify-center p-3 rounded-lg bg-bg border border-muted/10 text-center gap-1.5">
                            <div class="flex items-center gap-2">
                                <x-ui.icon :name="$iconName" variant="outline" class="w-5 h-5 text-primary" />
                                <x-ui.icon :name="$iconName" variant="solid" class="w-5 h-5 text-accent" />
                            </div>
                            <span class="text-xs font-mono text-muted truncate max-w-full">{{ $iconName }}</span>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </section>

        {{-- 3. Tombol (Button) --}}
        <section id="buttons" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-text">3. Tombol (&lt;x-ui.button&gt;)</h2>
                <p class="text-sm text-muted">Props: <code class="font-mono text-xs">variant</code> ('primary'|'secondary'|'danger'|'ghost'), <code class="font-mono text-xs">loading</code> (bool), <code class="font-mono text-xs">disabled</code> (bool), <code class="font-mono text-xs">icon</code> (string), <code class="font-mono text-xs">href</code> (string). Tinggi min 44px.</p>
            </div>

            <x-ui.card>
                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.button variant="primary">Primary</x-ui.button>
                    <x-ui.button variant="secondary">Secondary</x-ui.button>
                    <x-ui.button variant="danger">Danger</x-ui.button>
                    <x-ui.button variant="ghost">Ghost</x-ui.button>

                    <x-ui.button variant="primary" icon="centang">Dengan Ikon</x-ui.button>
                    <x-ui.button variant="secondary" iconRight="chevron-right">Ikon Kanan</x-ui.button>
                    <x-ui.button variant="primary" :loading="true">Memuat</x-ui.button>
                    <x-ui.button variant="primary" :disabled="true">Dinonaktifkan</x-ui.button>
                </div>
            </x-ui.card>
        </section>

        {{-- 4. Formulir (Input, Select, Textarea, Checkbox) --}}
        <section id="forms" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-text">4. Komponen Formulir</h2>
                <p class="text-sm text-muted">Input, Select, Textarea, Checkbox. Selalu berlabel terhubung, teks bantu, pesan galat, dan aria-describedby.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-ui.card title="Input & Select">
                    <div class="space-y-4">
                        <x-ui.input label="Nama Lengkap" name="name" placeholder="Masukkan nama sesuai KTP" :required="true" hint="Sesuai dokumen resmi" />
                        <x-ui.input label="Nomor Telepon" name="phone" value="08123456789" hint="Format nomor Indonesia" />
                        <x-ui.input label="Email dengan Galat" name="email" value="format-salah" error="Format alamat email tidak valid." />
                        <x-ui.input label="Input Dinonaktifkan" name="disabled_field" value="Nilai terkunci" :disabled="true" />
                        <x-ui.input label="Input Memuat (Skeleton)" :loading="true" />

                        <x-ui.select label="Pilih Kota" name="city" placeholder="Pilih area" :options="['pekanbaru' => 'Kota Pekanbaru', 'kampar' => 'Kabupaten Kampar']" hint="Wilayah operasional saat ini" />
                        <x-ui.select label="Pilih Opsi dengan Galat" name="bad_select" error="Anda wajib memilih salah satu opsi." :options="['1' => 'Pilihan A', '2' => 'Pilihan B']" />
                    </div>
                </x-ui.card>

                <x-ui.card title="Textarea & Checkbox">
                    <div class="space-y-4">
                        <x-ui.textarea label="Catatan Tambahan" name="notes" placeholder="Tuliskan catatan kondisi kendaraan..." hint="Maksimal 500 karakter" rows="3" />
                        <x-ui.textarea label="Textarea Galat" name="bad_notes" error="Catatan wajib diisi bila status caution." rows="2" />

                        <div class="pt-2 border-t border-muted/15 space-y-2">
                            <x-ui.checkbox name="agree" label="Saya menyetujui Syarat dan Ketentuan SIMOKAS" hint="Persetujuan wajib untuk mendaftar" :required="true" />
                            <x-ui.checkbox name="terms_err" label="Persetujuan Kebijakan Privasi" error="Anda harus menyetujui kebijakan privasi sebelum melanjutkan." />
                            <x-ui.checkbox name="opt_in" label="Terima ringkasan notifikasi mingguan (Aktif)" :checked="true" />
                            <x-ui.checkbox name="locked" label="Opsi terkunci" :disabled="true" />
                            <x-ui.checkbox label="Checkbox Memuat" :loading="true" />
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </section>

        {{-- 5. Status Chip & Badges --}}
        <section id="status-chips" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-text">5. Status Chip & Lencana</h2>
                <p class="text-sm text-muted">Selalu teks + ikon + warna. Props status-chip: <code class="font-mono text-xs">label</code>, <code class="font-mono text-xs">variant</code> ('success'|'warning'|'danger'|'primary'|'muted'), <code class="font-mono text-xs">icon</code>.</p>
            </div>

            <x-ui.card title="Status Chip (Tabel UI_GUIDELINES Bagian 4)">
                <div class="flex flex-wrap gap-2.5 items-center">
                    <x-ui.status-chip label="Aktif" variant="success" icon="centang" />
                    <x-ui.status-chip label="Menunggu Validasi" variant="warning" icon="jam" />
                    <x-ui.status-chip label="Dalam Inspeksi" variant="primary" icon="kaca-pembesar" />
                    <x-ui.status-chip label="Dalam Transaksi" variant="primary" icon="dua-panah" />
                    <x-ui.status-chip label="Terjual" variant="muted" icon="tag" />
                    <x-ui.status-chip label="Ditolak" variant="danger" icon="silang" />
                    <x-ui.status-chip label="Nonaktif" variant="muted" icon="jeda" />
                    <x-ui.status-chip label="Menunggu Inspektor" variant="primary" icon="jam-pasir" />
                    <x-ui.status-chip label="Diambil" variant="primary" icon="orang" />
                    <x-ui.status-chip label="Dijadwalkan" variant="primary" icon="kalender" />
                    <x-ui.status-chip label="Menunggu Konfirmasi" variant="warning" icon="dokumen" />
                    <x-ui.status-chip label="Perlu Perhatian" variant="warning" icon="segitiga-peringatan" />
                    <x-ui.status-chip label="Tidak Direkomendasikan" variant="danger" icon="silang" />
                </div>

                <div class="mt-4 pt-4 border-t border-muted/15 flex flex-wrap gap-3 items-center">
                    <span class="text-xs font-semibold text-muted">Lencana Inspeksi:</span>
                    <x-ui.badge-inspected :date="$now" />
                    <x-ui.badge-inspected :compact="true" />
                </div>
            </x-ui.card>
        </section>

        {{-- 6. Uang & Waktu --}}
        <section id="money-datetime" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-text">6. Uang & Waktu</h2>
                <p class="text-sm text-muted">Memakai <code class="font-mono text-xs">App\Support\Money</code> dan <code class="font-mono text-xs">App\Support\Clock</code>.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.card title="&lt;x-ui.money&gt;">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm py-1 border-b border-muted/10">
                            <span class="text-muted">Biaya Inspeksi Motor:</span>
                            <x-ui.money :amount="15000" class="text-base font-bold text-primary" />
                        </div>
                        <div class="flex items-center justify-between text-sm py-1 border-b border-muted/10">
                            <span class="text-muted">Biaya Inspeksi Mobil:</span>
                            <x-ui.money :amount="50000" class="text-base font-bold text-primary" />
                        </div>
                        <div class="flex items-center justify-between text-sm py-1">
                            <span class="text-muted">Harga Kendaraan:</span>
                            <x-ui.money :amount="185000000" class="text-lg font-bold text-primary" />
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="&lt;x-ui.datetime&gt;">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm py-1 border-b border-muted/10">
                            <span class="text-muted">Format Standar (WIB):</span>
                            <x-ui.datetime :value="$now" :showIcon="true" />
                        </div>
                        <div class="flex items-center justify-between text-sm py-1 border-b border-muted/10">
                            <span class="text-muted">Format Tanggal Saja:</span>
                            <x-ui.datetime :value="$now" format="date" />
                        </div>
                        <div class="flex items-center justify-between text-sm py-1">
                            <span class="text-muted">Format Waktu Saja:</span>
                            <x-ui.datetime :value="$now" format="time" />
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </section>

        {{-- 7. Stepper & Skor Melingkar --}}
        <section id="stepper-score" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-text">7. Stepper & Skor Melingkar</h2>
                <p class="text-sm text-muted">Indikator tahapan status job dan hasil rekomendasi inspeksi.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2">
                    <x-ui.card title="Stepper Tahapan Job">
                        <div class="space-y-6">
                            <div>
                                <p class="text-xs font-semibold text-muted mb-3">Tata Letak Horizontal:</p>
                                <x-ui.stepper :steps="$sampleSteps" />
                            </div>

                            <div class="pt-4 border-t border-muted/15">
                                <p class="text-xs font-semibold text-muted mb-3">Tata Letak Vertikal:</p>
                                <x-ui.stepper :steps="$sampleSteps" orientation="vertical" />
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <div>
                    <x-ui.card title="Skor Ring">
                        <div class="space-y-4">
                            <x-ui.score-ring :score="88" recommendation="recommended" />
                            <div class="pt-2 border-t border-muted/15">
                                <x-ui.score-ring :score="65" recommendation="caution" />
                            </div>
                        </div>
                    </x-ui.card>
                </div>
            </div>
        </section>

        {{-- 8. Kartu Listing --}}
        <section id="listing-cards" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-text">8. Kartu Listing (&lt;x-ui.listing-card&gt;)</h2>
                <p class="text-sm text-muted">Thumbnail foto (lazy-loaded), harga, tahun, kilometer, area, dan lencana.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                <x-ui.listing-card title="Honda Vario 160 ABS 2023 Terawat Siap Pakai"
                                   :price="24500000"
                                   :year="2023"
                                   :mileage="12000"
                                   area="Pekanbaru Kota"
                                   :inspected="true"
                                   :inspectedDate="$now"
                                   statusLabel="Aktif"
                                   statusVariant="success"
                                   statusIcon="centang" />

                <x-ui.listing-card title="Toyota Avanza 1.5 G MT 2021 Tangan Pertama"
                                   :price="195000000"
                                   :year="2021"
                                   :mileage="45000"
                                   area="Tampan"
                                   :inspected="false"
                                   statusLabel="Dalam Transaksi"
                                   statusVariant="primary"
                                   statusIcon="dua-panah" />

                <x-ui.listing-card title="Listing Dalam Keadaan Memuat"
                                   :price="0"
                                   :loading="true" />
            </div>
        </section>

        {{-- 9. Peringatan & Modal --}}
        <section id="alert-modal" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-text">9. Peringatan (Alert) & Modal</h2>
                <p class="text-sm text-muted">Notifikasi dinamis dan konfirmasi tindakan tak dapat dibatalkan.</p>
            </div>

            <div class="space-y-3">
                <x-ui.alert variant="info" title="Informasi Penting" message="Seluruh pembayaran pada versi saat ini menggunakan sistem simulasi." />
                <x-ui.alert variant="success" title="Pembayaran Berhasil" message="Tagihan inspeksi telah lunas. Job Anda sedang menunggu inspektor." />
                <x-ui.alert variant="warning" title="Perlu Verifikasi" message="Dokumen sertifikat teknisi sedang ditinjau oleh tim admin." />
                <x-ui.alert variant="danger" title="Gagal Memuat Data" message="Koneksi terputus saat mengambil data kendaraan." retryLabel="Coba lagi" retryHref="#tokens" />
            </div>

            <div class="pt-4">
                <x-ui.card title="Contoh Pemicu Modal">
                    <p class="text-sm text-muted mb-4">Modal menjebak fokus dan menutup otomatis dengan tombol Esc.</p>

                    <div x-data>
                        <x-ui.button type="button" variant="danger" @click="$dispatch('open-modal', 'modal-demo')">
                            Buka Modal Konfirmasi
                        </x-ui.button>

                        <x-ui.modal name="modal-demo"
                                    title="Batalkan Pekerjaan Inspeksi?"
                                    description="Tindakan ini tidak dapat dibatalkan. Job akan dikembalikan ke antrean terbuka dan tercatat di riwayat."
                                    confirmText="Ya, Batalkan"
                                    confirmVariant="danger"
                                    cancelText="Kembali" />
                    </div>
                </x-ui.card>
            </div>
        </section>

        {{-- 10. Kamera & Keadaan Kosong --}}
        <section id="camera-empty" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-text">10. Kamera (&lt;x-ui.camera-capture&gt;) & Keadaan Kosong</h2>
                <p class="text-sm text-muted">Kerangka kamera (siap ambil, pratinjau, izin ditolak) dan empty-state.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-ui.card title="Kerangka Kamera: Siap Ambil">
                    <x-ui.camera-capture label="Foto Tampak Depan Kendaraan" />
                </x-ui.card>

                <x-ui.card title="Kerangka Kamera: Izin Ditolak">
                    <x-ui.camera-capture label="Foto Nomor Rangka" :permissionDenied="true" />
                </x-ui.card>
            </div>

            <div class="pt-4">
                <x-ui.empty-state icon="kaca-pembesar"
                                  title="Belum Ada Hasil Pencarian"
                                  description="Kami tidak menemukan kendaraan yang sesuai dengan kriteria filter Anda. Coba ubah kata kunci atau rentang harga."
                                  actionLabel="Atur Ulang Filter"
                                  actionUrl="#tokens" />
            </div>
        </section>

        {{-- 11. Pagination & Skeleton --}}
        <section id="pagination-skeleton" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-text">11. Pagination & Skeleton</h2>
                <p class="text-sm text-muted">Kontrol halaman mobile-first dan placeholder memuat.</p>
            </div>

            <x-ui.card title="Pagination (12 item per halaman)">
                <x-ui.pagination :currentPage="2" :totalPages="5" :total="54" previousUrl="#prev" nextUrl="#next" />
            </x-ui.card>

            <x-ui.card title="Keluarga Skeleton (&lt;x-ui.skeleton&gt;)">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="space-y-2">
                        <span class="text-xs text-muted">Variant Text & Title:</span>
                        <x-ui.skeleton variant="title" />
                        <x-ui.skeleton variant="text" />
                        <x-ui.skeleton variant="text" class="w-2/3" />
                    </div>

                    <div class="space-y-2">
                        <span class="text-xs text-muted">Variant Avatar & Badge:</span>
                        <div class="flex items-center gap-3">
                            <x-ui.skeleton variant="avatar" />
                            <x-ui.skeleton variant="badge" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <span class="text-xs text-muted">Variant Button:</span>
                        <x-ui.skeleton variant="button" />
                    </div>
                </div>
            </x-ui.card>
        </section>

        {{-- 12. Kartu Dashboard (Dash-Card & Stat-Card) --}}
        <section id="dashboard-cards" class="space-y-4">
            <div>
                <h2 class="text-xl font-bold text-text">12. Kartu Dashboard (&lt;x-ui.dash-card&gt; & &lt;x-ui.stat-card&gt;)</h2>
                <p class="text-sm text-muted">Komponen penyusun kartu dashboard: pembungkus dengan header/aksi dan kartu metrik horizontal.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-ui.dash-card title="Contoh Dash-Card" subtitle="Subjudul opsional">
                    <x-slot:action>
                        <span class="text-xs font-bold text-forest">Aksi Kanan</span>
                    </x-slot:action>
                    <p class="text-sm text-slate">Konten di dalam dash-card dengan sudut rounded-2xl dan padding standar.</p>
                </x-ui.dash-card>

                <div class="flex gap-3">
                    <x-ui.stat-card value="12" label="Tersedia" href="#tokens" class="flex-1" />
                    <x-ui.stat-card value="4" label="Aktif" class="flex-1" />
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
