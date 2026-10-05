# Panduan Berkontribusi

## 1. Alur kerja

1. Ambil satu fitur atau tugas. Pastikan spesifikasinya ada (flow doc atau `features/<nama>.md`).
2. Buat branch dari `main` terbaru.
3. Kerjakan kode **dan test** bersamaan, kecil dan sering di-commit.
4. Jalankan `composer check` lokal.
5. Buka PR ke `main`. CI harus hijau.
6. Minimal **1 reviewer** dari anggota tim lain menyetujui.
7. Merge dengan *squash*. Hapus branch.

`main` selalu bisa di-deploy. Jangan push langsung ke `main`.

## 2. Branch

`<tipe>/<domain>-<ringkasan>` dalam bahasa Inggris, huruf kecil.

| Tipe | Contoh |
| --- | --- |
| `feature` | `feature/inspection-take-job` |
| `fix` | `fix/payment-double-click` |
| `docs` | `docs/flow-transaction` |
| `chore` | `chore/ci-coverage` |

## 3. Commit

Format *Conventional Commits*, bahasa Inggris, bentuk perintah:

```
feat(inspection): add atomic take-job action
fix(payment): reject payment on expired invoice
test(result): cover critical item recommendation
docs(flows): add transaction cancellation rules
```

Tipe: `feat`, `fix`, `test`, `docs`, `refactor`, `chore`, `perf`. Satu commit = satu perubahan logis.

## 4. Pull request

- Judul mengikuti format commit.
- Isi PR memakai [template](../.github/pull_request_template.md): apa yang berubah, mengapa, flow dan test case yang tercakup, cara menguji, tangkapan layar untuk perubahan UI.
- PR **kecil dan fokus pada satu domain**. Perubahan yang menyentuh lebih dari satu domain dipecah, atau dijelaskan alasannya di deskripsi.
- Perubahan pada kontrak, event, `Support`, komponen UI bersama, atau migrasi domain lain dipisah menjadi PR sendiri dan diberi label `shared`.
- Jangan memasukkan perubahan format yang tidak berkaitan.

## 5. Review

Reviewer memeriksa:

- [ ] Perilaku sesuai flow doc dan PRD.
- [ ] Test lengkap sesuai [TESTING.md](TESTING.md) (T1–T9).
- [ ] Batas domain tidak dilanggar ([ARCHITECTURE.md](ARCHITECTURE.md)).
- [ ] Otorisasi ada di setiap titik masuk dan data persis tidak bocor.
- [ ] Operasi uang dan status idempoten, bertransaksi, dan aman dari rebutan.
- [ ] UI mengikuti [UI_GUIDELINES.md](UI_GUIDELINES.md).
- [ ] Tidak ada rahasia, `dd()`, atau kode mati.

Reviewer menandai komentar sebagai **blocking** atau **nit**. Penulis menjawab semua komentar sebelum merge.

## 6. Mengerjakan fitur tanpa saling mengganggu

- Satu orang memegang satu domain pada satu waktu. Catat siapa mengerjakan apa di papan tugas tim.
- Jika fitur Anda butuh sesuatu dari domain lain, **minta kontrak atau event** (PR kecil terpisah), jangan mengubah isi domain itu.
- Konflik migrasi dihindari karena tiap domain punya folder migrasi sendiri dan nama berstempel waktu.
- Konflik `bootstrap/providers.php` diselesaikan dengan menyimpan kedua baris.
- Rebase atau merge `main` ke branch Anda sebelum membuka PR.

## 7. Definition of Done

Sebuah tugas selesai bila **semua** terpenuhi:

- [ ] Perilaku sesuai spesifikasi (flow doc / fitur) dan acceptance criteria PRD yang relevan.
- [ ] Unit test dan feature test ditulis dan lulus (nama memuat ID test case).
- [ ] `composer check` bersih (Pint, Larastan, Pest) dan CI hijau.
- [ ] Otorisasi lewat Policy dan diuji untuk semua role.
- [ ] UI memenuhi daftar periksa di UI_GUIDELINES bagian 12.
- [ ] Flow doc dan dokumen lain diperbarui bila perilaku berubah.
- [ ] Migrasi bisa dijalankan dari nol (`migrate:fresh --seed`) tanpa galat.
- [ ] Tidak ada [OPEN] baru yang tersembunyi di kode. Pertanyaan dicatat di flow doc.
- [ ] Sudah di-review dan di-merge ke `main`.

## 8. Keamanan

- Jangan memasukkan `.env`, kunci, atau data pribadi ke repositori.
- Data demo hanya data fiktif. Akun demo dinonaktifkan setelah lomba.
- Laporkan kerentanan langsung ke tim, bukan lewat isu publik.
