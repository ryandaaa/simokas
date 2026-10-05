# AGENTS.md

Panduan lengkap untuk agen pengodean ada di [`CLAUDE.md`](CLAUDE.md). Baca itu terlebih dahulu. Berlaku untuk semua agen, bukan hanya Claude.

Mengerjakan kartu kanban: ikuti [`docs/AGENT_TASK.md`](docs/AGENT_TASK.md).

Ringkasan aturan:

1. Satu fitur = satu domain di `app/Domains/<Domain>`. Jangan mengubah domain lain kecuali lewat kontrak atau event, dan itu dalam PR terpisah berlabel `shared`.
2. Logika bisnis di Action. Otorisasi di Policy. Data persis tidak pernah di respons publik.
3. Setiap kode baru wajib unit test dan feature test dalam PR yang sama. Nama test memuat ID test case dari `docs/flows/<modul>.md`.
4. Operasi uang dan status: idempoten, dalam transaksi database, aman dari rebutan. Uang hanya lewat `Support\Money`.
5. UI memakai token dan komponen bersama (`docs/UI_GUIDELINES.md`).
6. Dokumen dan teks UI berbahasa Indonesia. Kode, tabel, rute, dan commit berbahasa Inggris.
7. Jangan menebak aturan bisnis. Tandai [OPEN] dan tanyakan.
8. Jalankan `composer check` sebelum membuka PR.
