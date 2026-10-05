# 0001. Stack: Laravel + MySQL

- **Status:** Diterima (5 Oktober 2026)
- **Keputusan oleh:** tim

## Konteks

PRD v1.4 mengusulkan Next.js + Supabase + Vercel. Tim sudah memiliki hosting **Hostinger Cloud Startup** dan memilih memakai Laravel. Pengerjaan dilakukan 2–3 orang dengan waktu sangat sempit (P0 pada 8 Oktober).

## Keputusan

| Hal | Pilihan |
| --- | --- |
| Framework | Laravel 12, PHP 8.3 |
| Frontend | Blade + Livewire + Tailwind |
| Database | MySQL/MariaDB di Hostinger |
| Test | Pest, CI GitHub Actions |
| Real-time (P1) | Laravel Echo + Pusher (paket gratis), cadangan polling Livewire |
| Penyimpanan | Disk privat Laravel dengan tautan bertanda tangan |
| Struktur kode | Domain folder per fitur di `app/Domains` |

## Alasan

- Berjalan di hosting yang sudah dimiliki tanpa biaya dan risiko tambahan.
- Livewire memungkinkan 2 orang membangun UI interaktif tanpa API dan SPA terpisah.
- **MySQL, bukan PostgreSQL:** PostgreSQL tidak tersedia di paket Cloud Hostinger. PostgreSQL eksternal (Neon) menambah latensi, titik gagal saat demo, dan bergantung pada ekstensi `pdo_pgsql` di hosting yang belum terverifikasi. Tim memilih jalur paling aman.
- **Pusher, bukan Reverb:** Reverb membutuhkan proses yang terus hidup, yang kemungkinan tidak tersedia di paket hosting ini.
- **Domain folder, bukan paket modul:** tanpa dependensi tambahan, cukup untuk memisahkan pekerjaan antar anggota tim.

## Konsekuensi

- Row Level Security digantikan Policy dan Gate. Otorisasi harus ada di setiap titik masuk dan diuji.
- Aturan "satu job aktif per listing" memakai kolom virtual + indeks unik, bukan indeks unik parsial.
- Pengujian memakai MySQL, bukan SQLite.
- Tugas terjadwal bergantung pada cron hPanel setiap menit.

## Yang harus diverifikasi sebelum 8 Oktober [OPEN]

- Versi PHP 8.3, akses SSH dan Composer di paket.
- Cron per menit berjalan untuk `schedule:run`.
- Versi MySQL/MariaDB (kolom virtual berindeks unik).
- Koneksi keluar ke Pusher diizinkan.
