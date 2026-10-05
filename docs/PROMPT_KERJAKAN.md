# Prompt Mulai Bekerja

Satu-satunya yang perlu diganti: **nomor issue**. Seluruh aturan, alur, dan kriteria sudah ada di repo dan di issue, jadi prompt ini hanya menyuruh agen membacanya lalu mengikutinya.

Ganti `{{NOMOR_ISSUE}}` dengan nomor issue (misalnya `21` untuk `[JOB-2]`). Daftar nomor ada di [BACKLOG.md](BACKLOG.md).

Prasyarat sebelum memakai prompt:

1. Kartu sudah di kolom **Ready** dan kartu di bagian "Butuh" sudah di-merge ke `main`.
2. Repo lokal berada di `main` terbaru (`git switch main && git pull`).
3. Agen punya akses membaca issue (tool GitHub atau `gh`). Bila tidak, tempelkan isi issue di bawah prompt.

## 1. Prompt lengkap (disarankan)

````text
Kerjakan issue #{{NOMOR_ISSUE}} di repo ini.

Aturan pengerjaan ada di dalam repo. Jangan mengandalkan ingatan atau asumsi: baca file-nya.

## Langkah 0. Baca dulu, sebelum menyentuh kode
1. CLAUDE.md, lalu docs/AGENT_TASK.md. Ikuti alur di AGENT_TASK.md persis.
2. Isi lengkap issue #{{NOMOR_ISSUE}} (lewat tool GitHub atau `gh issue view {{NOMOR_ISSUE}}`).
   Jika tidak punya akses ke issue, BERHENTI dan minta saya menempelkan isinya.
3. Flow doc yang disebut di issue. Fokus pada aturan bisnis (-R), edge case (-E), dan test case (-T) yang terkait.
4. docs/ARCHITECTURE.md, docs/CONVENTIONS.md, docs/TESTING.md.
   Tambah docs/UI_GUIDELINES.md bila kartu menyentuh tampilan.

## Langkah 1. Periksa prasyarat
Bagian "Butuh" di issue: semua kartu itu harus sudah ada di main (kodenya sudah ter-merge).
Jika belum, BERHENTI dan laporkan kartu mana yang belum selesai.
Jangan mengerjakan kartu prasyarat itu dan jangan membuat pengganti sementara.

## Langkah 2. Rencana singkat
Tulis: file yang akan dibuat atau diubah, kontrak atau event yang dipakai, migrasi,
dan daftar test case ID yang akan ditulis.
Jika ada [OPEN] atau hal ambigu yang memengaruhi perilaku, BERHENTI dan tanya saya.
Jika tidak ada, lanjut tanpa menunggu persetujuan.

## Langkah 3. Kerjakan (test dulu)
- git fetch origin, lalu buat branch dari origin/main bernama
  feature/<domain>-<ringkasan-bahasa-inggris>   (contoh: feature/inspection-take-job).
- Tulis test untuk SEMUA test case ID di issue. Nama test diawali ID-nya, misalnya
  it('JOB-T03 ...'). Pastikan test gagal karena alasan yang benar, baru implementasikan sampai lulus.
- Setiap Action, Policy, Enum, dan Rule baru wajib unit test. Setiap rute, komponen Livewire,
  listener, dan command baru wajib feature test, termasuk otorisasi semua role dan edge case.
- Hanya ubah file di domain yang disebut di issue.
  Perubahan di domain lain, Support, kontrak, event, atau komponen UI bersama: JANGAN dikerjakan
  di sini. Catat di laporan sebagai saran PR terpisah berlabel `shared`.
- Logika bisnis di Action, otorisasi di Policy, data persis hanya lewat Resource privat,
  uang hanya lewat Support\Money, operasi uang dan status dalam transaksi DB dan idempoten.
- UI: pakai token dan komponen bersama, sediakan keadaan memuat, kosong, dan gagal.

## Langkah 4. Verifikasi
Jalankan `composer check` sampai bersih. Jika gagal, perbaiki penyebabnya.
Jangan menonaktifkan, men-skip, atau mengubah test agar lulus. Jangan menurunkan ambang cakupan
atau aturan lint tanpa meminta persetujuan saya.

## Langkah 5. Selesaikan
- Perbarui flow doc bila perilaku berubah, dan centang test case yang selesai.
- Commit memakai Conventional Commits, bahasa Inggris, bentuk perintah
  (contoh: feat(inspection): add atomic take-job action). Pakai konfigurasi git lokal apa adanya.
  Jangan menambahkan Co-Authored-By atau atribusi alat/model apa pun di commit maupun deskripsi PR.
- git push -u origin <branch>. Jangan membuka PR kecuali saya minta.

## Berhenti dan tanya jika
- Prasyarat belum di main.
- Ada [OPEN] yang mengubah perilaku, atau flow doc dan PRD bertentangan.
- Pekerjaan butuh perubahan di luar domain issue ini.
- Test lain gagal karena sebab di luar kartu ini.
- Ukuran pekerjaan jauh melebihi ukuran kartu (usulkan memecahnya).
Jangan menebak aturan bisnis. Pakai default yang tertulis di issue dan laporkan.

## Laporan akhir (wajib, format ini)
1. Kartu dan nama branch.
2. Kriteria issue satu per satu: selesai atau belum, dengan alasannya.
3. Test case ID yang lulus.
4. Hasil `composer check`.
5. Keputusan yang diambil dan [OPEN] yang ditemui.
6. Yang sengaja ditunda, risiko, dan saran PR `shared` (bila ada).
7. Draf deskripsi PR mengikuti .github/pull_request_template.md, berisi "Closes #{{NOMOR_ISSUE}}".
````

## 2. Prompt ringkas (untuk model kecil atau konteks terbatas)

````text
Kerjakan issue #{{NOMOR_ISSUE}}.
Baca CLAUDE.md dan docs/AGENT_TASK.md, ikuti alurnya persis.
Baca isi issue dan flow doc yang disebut. Cek bagian "Butuh" sudah di main; jika belum, berhenti.
Tulis test dulu (nama test memuat test case ID), lalu implementasi. Hanya ubah domain issue ini.
Jalankan composer check sampai bersih. Jangan skip atau ubah test agar lulus.
Commit Conventional Commits (Inggris), tanpa atribusi AI, push ke feature/<domain>-<ringkasan>. Jangan buka PR.
Jika ada [OPEN] atau hal ambigu: berhenti dan tanya.
Akhiri dengan laporan: kriteria issue (selesai atau belum), test lulus, hasil composer check,
keputusan yang diambil, draf deskripsi PR.
````

### Mode bertahap (model kecil sering lebih stabil)

Tambahkan satu baris ini di akhir salah satu prompt di atas:

```text
Kerjakan satu langkah saja, laporkan singkat, lalu berhenti dan tunggu saya mengetik "lanjut".
```

## 3. Varian

| Tujuan | Ganti atau tambahkan |
| --- | --- |
| Hanya rencana, belum kode | Tambahkan: `Berhenti setelah Langkah 2. Jangan menulis kode.` |
| Hanya test dulu | Tambahkan: `Berhenti setelah test ditulis dan terbukti gagal karena alasan yang benar. Belum implementasi.` |
| Perbaiki komentar review | `Perbaiki komentar review di PR #<nomor> untuk issue #{{NOMOR_ISSUE}}. Ikuti docs/AGENT_TASK.md. Jangan mengubah hal lain.` |
| Lanjutkan pekerjaan yang terhenti | `Lanjutkan issue #{{NOMOR_ISSUE}} di branch <nama-branch>. Periksa apa yang sudah ada, tunjukkan sisa pekerjaan, lalu lanjutkan sesuai prompt lengkap.` |

## 4. Prompt review (untuk reviewer, manusia atau AI)

````text
Review PR #<nomor_pr> terhadap issue #{{NOMOR_ISSUE}}. Jangan mengubah kode.
Baca CLAUDE.md, docs/AGENT_TASK.md, issue, flow doc yang disebut, dan docs/TESTING.md.
Periksa:
1. Setiap kriteria di issue terpenuhi, dan setiap test case ID di issue punya test bernama sama yang lulus.
2. Batas domain: hanya domain issue ini yang diubah, arch test hijau.
3. Otorisasi lewat Policy untuk semua role, data persis (koordinat, alamat, nomor rangka) tidak bocor.
4. Operasi uang dan status: idempoten, dalam transaksi DB, aman dari rebutan, memakai Support\Money.
5. Test bermakna (ada asersi efek, bukan sekadar menjalankan kode), tidak ada skip atau test yang dilemahkan.
6. UI sesuai docs/UI_GUIDELINES.md bila ada perubahan tampilan.
7. CI hijau. Baca log bila gagal.
8. Tidak ada rahasia, dd(), kode mati, atau atribusi AI di commit.
Keluarkan temuan terurut: MENGHALANGI MERGE, sebaiknya diperbaiki, saran. Sertakan file:baris dan alasan.
````

## 5. Cara memasangnya

| Alat | Cara |
| --- | --- |
| Alat apa pun | Simpan prompt sebagai snippet atau template teks. Ganti `{{NOMOR_ISSUE}}` saat dipakai |
| Claude Code (opsional) | Simpan prompt lengkap sebagai `.claude/commands/kerjakan.md` dengan `{{NOMOR_ISSUE}}` diganti `$ARGUMENTS`, lalu jalankan `/kerjakan 21` |
| Agen otomatis | Isi `{{NOMOR_ISSUE}}` dari nomor issue pada kartu yang digeser ke **Ongoing** |

File `.claude/commands/` tidak dibuat di repo ini secara bawaan supaya repo tetap netral terhadap alat. Tim yang memakainya boleh menambahkan sendiri.

## 6. Alur singkat tim

1. Pilih kartu di **Ready**, geser ke **Ongoing**, isi assignee.
2. `git switch main && git pull`.
3. Jalankan prompt dengan nomor issue kartu itu.
4. Baca laporan akhir. Bila ada [OPEN], jawab dan lanjutkan.
5. Buka PR dari draf deskripsinya, geser kartu ke **Review**.
6. Reviewer memakai prompt review di atas, atau meninjau manual dengan daftar yang sama.
