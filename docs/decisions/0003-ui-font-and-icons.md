# 0003. UI Font dan Ikon: Satoshi Self-Hosted dan Inline SVG Heroicons

- **Status:** Diterima (5 Oktober 2026), Diamandemen (5 Oktober 2026)
- **Keputusan oleh:** tim

## Konteks

UI SIMOKAS menganut prinsip modern minimalis, bersih, dan terpercaya (`docs/UI_GUIDELINES.md`). Dibutuhkan satu keluarga font yang konsisten dan set ikon untuk status, formulir, dan indikator. Sesuai aturan arsitektur dan kepatuhan privasi (UU PDP, PRD Bagian 20.5), aset pihak ketiga tidak boleh memuat pelacak atau bergantung pada CDN luar (seperti Google Fonts). Selain itu, bundle harus tetap ringan dan efisien di perangkat seluler dengan keterbatasan jaringan.

## Keputusan

| Aspek | Pilihan |
| --- | --- |
| Tipografi utama | **Satoshi** (Indian Type Foundry / Fontshare) *(menggantikan Plus Jakarta Sans)* |
| Cadangan font | `system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif` |
| Distribusi font | Berkas WOFF2 lokal di `resources/fonts/satoshi/` di-bundle oleh Vite (`/build/assets/*.woff2`), tanpa CDN eksternal |
| Set ikon | Heroicons (outline untuk umum, solid untuk status) |
| Implementasi ikon | Komponen Blade `<x-ui.icon>` dengan inline SVG Heroicons |
| Paket ikon tambahan | **Tidak ada.** Dilarang memasang pustaka ikon eksternal (Blade Heroicons, Lucide, FontAwesome, dsb.) |

## Alasan

1. **Keamanan & Privasi:** Mengunduh font dari Google Fonts atau CDN publik berisiko membocorkan alamat IP pengguna ke pihak ketiga dan berpotensi melanggar privasi pengguna. Self-hosting sepenuhnya mengeliminasi risiko ini.
2. **Performa:** Font berformat woff2 sangat hemat ukuran (sekitar 27 kB per bobot untuk latin), dimuat secara lokal dengan `font-display: block` (diubah dari `swap` pada 7 Oktober 2026 agar halaman tidak berkedip dari font bawaan ke Satoshi) tanpa perlu memuat CDN luar.
3. **Ukuran Bundle Ikon:** Memasang paket ikon penuh (seperti Blade Heroicons dengan ratusan file/class) menambah ukuran vendor dan jejak memori yang tidak perlu. Dengan implementasi inline SVG di `<x-ui.icon>`, hanya ikon yang benar-benar dipakai di aplikasi yang disimpan di dalam kode sumber.
4. **Fleksibilitas:** Inline SVG memungkinkan kontrol pewarnaan dan ukuran langsung melalui kelas utilitas Tailwind (`text-primary`, `text-success`, `w-5 h-5`) serta transisi CSS yang mulus.

## Pembaruan: Penggantian Font ke Satoshi (5 Oktober 2026)

- **Keputusan Penggantian:** Tipografi utama diganti dari Plus Jakarta Sans ke **Satoshi** (karya Indian Type Foundry, didistribusikan lewat Fontshare di `https://www.fontshare.com/fonts/satoshi`). Pilihan Plus Jakarta Sans sebelumnya ditandai sebagai **digantikan** (*superseded*).
- **Lisensi:** Satoshi dilisensikan di bawah **ITF Free Font License (FFL) Version 2.0 (17 Aug 2026)**. Lisensi ini secara eksplisit mengizinkan penggunaan komersial maupun pribadi secara gratis tanpa batas waktu, serta mengizinkan *self-hosting* web font pada infrastruktur sendiri melalui `@font-face`. Salinan lengkap teks lisensi disimpan di [`resources/fonts/satoshi/LICENSE.txt`](../../resources/fonts/satoshi/LICENSE.txt).
- **Berkas yang Disimpan:** Hanya berkas WOFF2 Latin untuk bobot Regular (400, ~27.2 kB), Medium (500, ~27.3 kB), dan Bold (700, ~26.7 kB) di `resources/fonts/satoshi/`. Tidak ada berkas zip, TTF, OTF, atau format lain yang dikomit ke repositori.
- **Pencabutan Dependensi npm:** Dependensi `@fontsource-variable/plus-jakarta-sans` telah dicabut (*uninstalled*) dari `package.json` dan `package-lock.json`, serta `@import`-nya dihapus dari `resources/css/app.css`. Tidak ada penambahan dependensi npm baru.
- **Alasan Penggantian:** Satoshi menghadirkan keterbacaan yang sangat baik pada layar seluler dengan karakteristik sans-serif geometris-humanis yang modern, proporsional untuk teks antarmuka dan angka, serta independen dari pustaka dependensi pihak ketiga.

## Konsekuensi

- Dependensi dev npm `@fontsource-variable/plus-jakarta-sans` telah dicabut; font sepenuhnya dikelola secara lokal di `resources/fonts/satoshi/` dan didaftarkan melalui `@font-face` di `resources/css/app.css`.
- Penambahan bobot font di masa mendatang harus mempertimbangkan anggaran ukuran bundle aset.
- Penambahan ikon baru di masa depan harus didaftarkan di dalam komponen `<x-ui.icon>`.

