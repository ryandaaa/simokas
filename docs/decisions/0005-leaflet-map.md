# 0005. Peta Leaflet + OpenStreetMap untuk titik listing (LST-2)

- **Status:** Diterima (5 Oktober 2026, kartu LST-2 / issue #11).
- **Konteks:** PRD 16.1 mewajibkan penjual memilih titik di peta dan membatasi titik di wilayah Pekanbaru. UI_GUIDELINES bagian 10 mengizinkan Leaflet dimuat hanya di halaman yang memakai peta.

## Keputusan

1. **Leaflet sebagai satu-satunya dependensi npm baru** (`leaflet@^1.9.4`, lisensi **BSD-2-Clause**). Tidak ada API key, tidak ada biaya, sesuai PRD bagian 10 ("Leaflet + OpenStreetMap: gratis, tanpa kartu kredit atau API key").
2. **Aset dihosting lokal lewat bundel Vite, bukan CDN.** Entri `resources/js/listing-map.js` (+ CSS Leaflet dan ikon marker yang diimpor ulang agar tidak pecah di bundel Vite) dibangun menjadi `public/build/assets/listing-map-*`. Alasan: privasi (tidak memanggil CDN pihak ketiga saat halaman dibuka), keandalan versi, dan kepatuhan font/self-hosting yang sudah dipakai proyek (Satoshi).
3. **Dimuat hanya di halaman yang memakai peta.** Layout global (`x-layouts.app`) tidak menyentuh Leaflet; komponen Livewire buat/edit iklan memuat `@vite('resources/js/listing-map.js')` di dalam `@assets`, yang Livewire injeksikan ke `<head>` tanpa menggeser root komponen. Feature test menjaga root tetap `<div>` dan layout tetap tidak menyebut `leaflet`.
4. **Tile dari server OpenStreetMap untuk skala demo**, `https://tile.openstreetmap.org/{z}/{x}/{y}.png`, maks zoom 19.
5. **Atribusi OSM wajib dan terlihat**: kontrol atribusi Leaflet aktif plus keterangan di bawah peta ("Data peta © kontributor OpenStreetMap" + tautan copyright).

## Kebijakan penggunaan tile OSM (ringkas)

- Tile OSM adalah layanan sumbangan untuk penggunaan ringan; aplikasi dilarang mengunduh massal, membuka banyak tab tile bersamaan, atau menyembunyikan atribusi (sumber: kebijakan tile OSMF).
- Skala lomba/demo SIMOKAS (satu peta per form listing) berada dalam batas wajar.
- Bila trafik produksi tumbuh: pasang cache proxy tile sendiri atau pindah ke penyedia tile komersial. Itu PR terpisah, bukan bagian kartu ini.

## Batas wilayah Pekanbaru [USULAN]

PRD 16.1 tidak memberi data batas resmi dan kartu ini melarang mengunduh data batas dari internet. Maka `PekanbaruBoundary` memakai **bounding box konservatif** (lat 0,40–0,63; lng 101,28–101,76; pusat 0,5071 / 101,4478) sebagai konstanta terdokumentasi. API `contains()` dirancang agar mudah diganti poligon resmi (misalnya dari berkas GeoJSON) tanpa mengubah Rule, Action, atau form.

## Titik perkiraan publik [USULAN]

`ApproximateLocation::fromExact()` membulatkan koordinat ke 2 desimal (kisi ±1,1 km, selisih maks terdokumentasi 800 m). Deterministik dan tidak membocorkan pin asli. Dipakai CAT-2 untuk peta publik tanpa penanda persis.

## Aksesibilitas dan fallback

- Peta satu jari di HP (drag/touch bawaan Leaflet aktif; zoom roda mouse dimatikan agar tidak membajak scroll halaman).
- Tanpa JavaScript: `<noscript>` + kolom garis lintang/bujur tetap bisa diisi manual.
- Tile gagal: peringatan jelas + kolom manual tetap berfungsi.
- Pengujian peta di browser bersifat manual (tidak ada test browser otomatis di repo); dicatat di laporan kartu.
