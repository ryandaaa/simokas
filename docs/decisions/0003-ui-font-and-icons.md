# 0003. UI Font dan Ikon: Plus Jakarta Sans Self-Hosted dan Inline SVG Heroicons

- **Status:** Diterima (5 Oktober 2026)
- **Keputusan oleh:** tim

## Konteks

UI SIMOKAS menganut prinsip modern minimalis, bersih, dan terpercaya (`docs/UI_GUIDELINES.md`). Dibutuhkan satu keluarga font yang konsisten dan set ikon untuk status, formulir, dan indikator. Sesuai aturan arsitektur dan kepatuhan privasi (UU PDP, PRD Bagian 20.5), aset pihak ketiga tidak boleh memuat pelacak atau bergantung pada CDN luar (seperti Google Fonts). Selain itu, bundle harus tetap ringan dan efisien di perangkat seluler dengan keterbatasan jaringan.

## Keputusan

| Aspek | Pilihan |
| --- | --- |
| Tipografi utama | **Plus Jakarta Sans** via paket npm `@fontsource-variable/plus-jakarta-sans` |
| Cadangan font | `system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif` |
| Distribusi font | Di-bundle lokal oleh Vite (`/build/assets/*.woff2`), tanpa CDN eksternal |
| Set ikon | Heroicons (outline untuk umum, solid untuk status) |
| Implementasi ikon | Komponen Blade `<x-ui.icon>` dengan inline SVG Heroicons |
| Paket ikon tambahan | **Tidak ada.** Dilarang memasang pustaka ikon eksternal (Blade Heroicons, Lucide, FontAwesome, dsb.) |

## Alasan

1. **Keamanan & Privasi:** Mengunduh font dari Google Fonts atau CDN publik berisiko membocorkan alamat IP pengguna ke pihak ketiga dan berpotensi melanggar privasi pengguna. Self-hosting sepenuhnya mengeliminasi risiko ini.
2. **Performa:** Font berformat variabel (*variable font*) woff2 sangat hemat ukuran (sekitar 27 kB untuk latin), mencakup variasi bobot dari regular hingga bold tanpa perlu memuat banyak file font terpisah.
3. **Ukuran Bundle Ikon:** Memasang paket ikon penuh (seperti Blade Heroicons dengan ratusan file/class) menambah ukuran vendor dan jejak memori yang tidak perlu. Dengan implementasi inline SVG di `<x-ui.icon>`, hanya ikon yang benar-benar dipakai di aplikasi yang disimpan di dalam kode sumber.
4. **Fleksibilitas:** Inline SVG memungkinkan kontrol pewarnaan dan ukuran langsung melalui kelas utilitas Tailwind (`text-primary`, `text-success`, `w-5 h-5`) serta transisi CSS yang mulus.

## Konsekuensi

- Menambah satu dependensi dev npm `@fontsource-variable/plus-jakarta-sans` (izin khusus telah dicatat di kartu issue #2).
- Penambahan ikon baru di masa depan harus didaftarkan di dalam komponen `<x-ui.icon>`.
