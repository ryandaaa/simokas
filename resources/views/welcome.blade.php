<x-layouts.app :title="__('ui.layout.home')">
    <div class="py-12 sm:py-16 text-center max-w-3xl mx-auto space-y-6">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-accent/15 text-accent text-xs font-semibold">
            <x-ui.icon name="perisai-centang" class="w-4 h-4" variant="solid" />
            <span>{{ __('ui.badges.simulation') }} — Kota Pekanbaru</span>
        </div>

        <h1 class="text-4xl sm:text-5xl font-extrabold text-text tracking-tight">
            Beli bekas, tenang karena sudah diinspeksi.
        </h1>

        <p class="text-lg text-muted max-w-2xl mx-auto">
            Platform jual-beli mobil dan motor bekas pertama di Pekanbaru dengan layanan inspeksi teknisi independen dan laporan kondisi terstruktur.
        </p>

        <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
            @if (app()->environment(['local', 'testing']))
                <x-ui.button :href="route('ui.gallery')" variant="primary" icon="dokumen">
                    Buka Galeri Komponen UI
                </x-ui.button>
            @endif

            <x-ui.button :href="url('#')" variant="secondary" icon="kaca-pembesar">
                Jelajahi Kendaraan
            </x-ui.button>
        </div>

        <div class="pt-12 grid grid-cols-1 sm:grid-cols-3 gap-6 text-left">
            <x-ui.card>
                <div class="space-y-2">
                    <x-ui.icon name="kaca-pembesar" class="w-6 h-6 text-primary" />
                    <h3 class="font-bold text-text text-base">Inspeksi Objektif</h3>
                    <p class="text-xs text-muted">Diperiksa langsung oleh teknisi bersertifikat di lapangan.</p>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="space-y-2">
                    <x-ui.icon name="dokumen" class="w-6 h-6 text-primary" />
                    <h3 class="font-bold text-text text-base">Laporan Terstruktur</h3>
                    <p class="text-xs text-muted">Skor kondisi menyeluruh dengan foto bertimestamp dari kamera.</p>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="space-y-2">
                    <x-ui.icon name="dua-panah" class="w-6 h-6 text-primary" />
                    <h3 class="font-bold text-text text-base">Transaksi Terpercaya</h3>
                    <p class="text-xs text-muted">Jual beli lebih aman tanpa khawatir kondisi kendaraan tersembunyi.</p>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
