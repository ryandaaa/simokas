# Mengerjakan Kartu dengan Agen

Panduan untuk anggota tim yang menyuruh agen (Claude Code atau lainnya) mengerjakan satu kartu di papan kanban, dan untuk agennya sendiri.

## Untuk anggota tim

1. Pastikan kartu berada di **Ready**: kartu di kolom **Butuh** sudah di-merge ke `main`.
2. Geser kartu ke **Ongoing** dan isi assignee.
3. Pastikan repo lokal berada di `main` terbaru.
4. Beri agen prompt dari [PROMPT_KERJAKAN.md](PROMPT_KERJAKAN.md). Satu-satunya yang diganti adalah nomor issue.

Agen dapat membaca issue lewat tool GitHub. Bila tidak ada akses, tempelkan isi issue ke prompt.

5. Tinjau PR yang dibuat agen. Geser kartu ke **Review**, lalu **Done** setelah di-merge.

Varian prompt (hanya rencana, hanya test, perbaikan review, lanjutkan) dan prompt review ada di [PROMPT_KERJAKAN.md](PROMPT_KERJAKAN.md).

## Untuk agen: alur kerja

### 1. Pahami

1. Baca issue: ID kartu, domain, flow doc, test case ID, prasyarat.
2. Baca `CLAUDE.md`, lalu dokumen yang relevan: [ARCHITECTURE](ARCHITECTURE.md), [CONVENTIONS](CONVENTIONS.md), [TESTING](TESTING.md), [UI_GUIDELINES](UI_GUIDELINES.md) (bila ada UI).
3. Baca `flows/<modul>.md` untuk modul kartu ini: diagram, aturan bisnis (`-R`), edge case (`-E`), test case (`-T`).
4. Baca bagian PRD yang dirujuk dan [decisions/0002-flow-decisions.md](decisions/0002-flow-decisions.md).
5. Periksa prasyarat: kartu di **Butuh** benar-benar sudah ada di `main`. Bila belum, **berhenti** dan laporkan.

### 2. Rencanakan

Tulis ringkasan singkat sebelum mengubah apa pun: file yang akan dibuat atau diubah, kontrak atau event yang dipakai, migrasi, dan test case yang akan ditulis. Jika ada yang [OPEN] atau ambigu dan memengaruhi perilaku, **tanyakan**, jangan menebak.

### 3. Kerjakan (test dulu)

1. Buat branch `feature/<domain>-<ringkasan>` dari `main` terbaru.
2. Tulis test untuk test case ID di issue. Nama test diawali ID (`it('JOB-T03 ...')`).
3. Implementasikan sampai test lulus. Pola: Action untuk logika, Policy untuk otorisasi, Resource untuk data publik vs privat, Enum untuk status.
4. Tambahkan test untuk edge case di flow doc yang relevan walau belum ada ID-nya, lalu beri ID di flow doc.
5. Ikuti [UI_GUIDELINES](UI_GUIDELINES.md): token dan komponen bersama, keadaan memuat/kosong/gagal, konfirmasi untuk aksi tak dapat dibatalkan.
6. Jalankan `composer check` sampai bersih.

### 4. Batas yang tidak boleh dilanggar

| Aturan | Bila terpaksa |
| --- | --- |
| Hanya ubah file di domain kartu ini | Perubahan di domain lain, `Support`, kontrak, event, atau komponen UI bersama dibuat sebagai **PR terpisah** berlabel `shared`, dan beri tahu manusia |
| Tidak mengubah migrasi yang sudah di-merge | Buat migrasi baru |
| Tidak menambah dependensi baru | Catat alasannya di `docs/decisions/` dan minta persetujuan |
| Tidak menambah tabel, kolom, atau status di luar PRD dan flow | Perbarui dokumen lebih dulu dan minta persetujuan |
| Tidak menonaktifkan, `skip`, atau mengubah test agar lulus | Perbaiki penyebabnya. Bila test memang salah, jelaskan di PR |
| Tidak menebak aturan bisnis | Tulis [OPEN] di flow doc, pakai default yang tercantum, dan laporkan |
| Tidak membocorkan data persis (koordinat, alamat, nomor rangka) | Pakai Resource privat dan Policy |
| Tidak menghitung uang di luar `Support\Money` | Pakai `Money::split` |

### 5. Selesaikan

1. Perbarui flow doc bila perilaku berubah. Centang test case yang selesai di flow doc.
2. Pastikan [Definition of Done](CONTRIBUTING.md#7-definition-of-done) terpenuhi.
3. Commit dengan format Conventional Commits berbahasa Inggris. Pakai konfigurasi git lokal apa adanya dan jangan menambahkan `Co-Authored-By` atau atribusi alat/model apa pun di commit maupun deskripsi PR.
4. Push branch (`git push -u origin <branch>`). **Jangan membuka PR kecuali diminta.** Siapkan draf deskripsi PR mengikuti [template](../.github/pull_request_template.md): kartu (`Closes #<nomor>`), domain, flow doc, daftar test case ID yang lulus, cara menguji, tangkapan layar untuk UI. Manusia yang membuka PR dari draf itu.
5. Laporkan ke manusia: apa yang selesai, keputusan yang diambil, hal [OPEN] yang ditemui, dan yang sengaja ditunda.

### 6. Kapan berhenti dan bertanya

- Prasyarat kartu belum di-merge.
- Ada [OPEN] yang mengubah perilaku, atau flow doc dan PRD saling bertentangan.
- Pekerjaan butuh perubahan di domain lain atau komponen bersama.
- Test yang ada gagal karena sebab di luar kartu ini.
- Estimasi jauh melebihi ukuran kartu. Usulkan memecahnya.

## Daftar periksa cepat untuk agen

- [ ] Prasyarat sudah di `main`.
- [ ] Sudah membaca flow doc dan test case ID kartu.
- [ ] Test ditulis lebih dulu dan memuat ID.
- [ ] Hanya menyentuh domain kartu ini.
- [ ] Otorisasi dan privasi data teruji.
- [ ] `composer check` bersih.
- [ ] Flow doc diperbarui, draf PR siap (template, `Closes #<nomor>`), PR dibuka hanya bila diminta.
