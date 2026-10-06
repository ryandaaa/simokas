# SIMOKAS

Aplikasi web jual-beli mobil dan motor bekas dengan inspeksi independen oleh inspektor bersertifikat. Wilayah awal: Pekanbaru.

- Spesifikasi produk: [`PRD_SIMOKAS_v1_6.md`](PRD_SIMOKAS_v1_6.md)
- Dokumentasi pengerjaan: [`docs/README.md`](docs/README.md)
- Panduan developer dan AI: [`CLAUDE.md`](CLAUDE.md)

## Prasyarat Lingkungan

- PHP 8.3+
- Composer 2.x
- Node.js 22.x
- MySQL / MariaDB

## Instalasi dan Setup Lokal

1. Pasang dependensi PHP dan Node:
   ```bash
   composer install
   npm ci
   ```

2. Konfigurasi file lingkungan:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Sesuaikan kredensial database di `.env`. Variabel `ADMIN_EMAIL` dan `ADMIN_PASSWORD` wajib diisi dengan kata sandi kuat (minimal 12 karakter) sebelum menjalankan seeder database (`php artisan db:seed`). Nilai placeholder `GANTI-DENGAN-PASSWORD-KUAT-MIN-12-KARAKTER` tidak akan lolos validasi seeder.

3. Kompilasi aset frontend:
   ```bash
   npm run build
   ```

## Perintah Verifikasi & Pengujian

```bash
composer lint        # Pint (cek gaya kode)
composer analyse     # Larastan (analisis statis)
composer test        # Pest (menjalankan pengujian)
composer check       # Lint + Analyse + Test (dijalankan di CI)
```
