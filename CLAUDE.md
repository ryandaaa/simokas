# SIMOKAS — Panduan untuk Developer dan AI

Aplikasi web jual-beli mobil dan motor bekas dengan inspeksi independen. Sumber kebenaran produk: [`PRD_SIMOKAS_v1_5.md`](PRD_SIMOKAS_v1_5.md). Aturan pengerjaan: folder [`docs/`](docs/README.md).

## Mengerjakan kartu kanban

Bila diminta mengerjakan sebuah issue/kartu, ikuti [`docs/AGENT_TASK.md`](docs/AGENT_TASK.md). Daftar kartu: [`docs/BACKLOG.md`](docs/BACKLOG.md).

## Stack

Laravel 12 (PHP 8.3), Blade + Livewire + Tailwind, MySQL/MariaDB, Pest, Pusher (cadangan polling). Hosting Hostinger Cloud Startup. Alasan: [`docs/decisions/0001-stack.md`](docs/decisions/0001-stack.md).

## Wajib dibaca sebelum menulis kode

1. [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md): struktur domain dan aturan antar-modul.
2. [`docs/CONVENTIONS.md`](docs/CONVENTIONS.md): penamaan, pola kode, dan database.
3. [`docs/TESTING.md`](docs/TESTING.md): setiap kode wajib punya unit test dan feature test.
4. [`docs/UI_GUIDELINES.md`](docs/UI_GUIDELINES.md): aturan tampilan.
5. [`docs/flows/<modul>.md`](docs/flows/00-overview.md): flow, edge case, dan daftar test case fitur yang dikerjakan.

## Aturan inti (ringkas)

- **Satu fitur = satu domain** di `app/Domains/<Domain>`. Jangan mengubah file domain lain kecuali lewat kontrak atau event (lihat ARCHITECTURE).
- **Logika bisnis di Action**, bukan di controller, komponen Livewire, atau model. Aturan uang dan status hanya ada di satu tempat.
- **Setiap kode baru wajib disertai test** dalam PR yang sama. PR tanpa test tidak di-merge.
- **Setiap aksi wajib diotorisasi** lewat Policy. Data persis (koordinat, alamat, nomor rangka) tidak pernah masuk respons publik.
- **Operasi uang dan status** idempoten, dibungkus transaksi database, dan memakai kunci baris atau pembaruan berkondisi.
- **Bahasa:** dokumen dan teks UI berbahasa Indonesia. Nama class, tabel, kolom, rute, variabel, dan pesan commit berbahasa Inggris.
- Jangan menambah dependensi baru tanpa mencatat alasannya di `docs/decisions/`.
- Jangan membuat tabel, kolom, atau status baru yang tidak ada di PRD atau flow tanpa memperbarui dokumennya terlebih dahulu.

## Perintah

Tersedia setelah kerangka proyek dibuat:

```bash
composer test        # Pest (unit + feature + arch)
composer lint        # Pint (cek gaya kode)
composer analyse     # Larastan
composer check       # lint + analyse + test, dijalankan CI
```

## Definition of Done

Lihat [`docs/CONTRIBUTING.md`](docs/CONTRIBUTING.md#definition-of-done). Ringkasnya: test lulus, lint dan analisis bersih, UI mengikuti guideline, flow doc diperbarui bila perilaku berubah.

## Menandai hal yang belum pasti

Pakai **[OPEN]** untuk pertanyaan yang belum diputuskan dan **[USULAN]** untuk keputusan sementara dari penyusun. Jangan menebak aturan bisnis: tulis sebagai [OPEN] di flow doc, lalu tanyakan ke tim.
