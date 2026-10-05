# 0002. Keputusan flow (celah PRD v1.5)

- **Status:** Diterima sebagian (5 Oktober 2026). Perlu disalin ke PRD v1.6.
- **Sumber:** jawaban tim atas lubang yang ditemukan saat memetakan flow.

Penanda: **[TIM]** diputuskan tim, **[USULAN]** diputuskan penyusun karena tim menyerahkan atau tidak menjawab, **[OPEN]** belum diputuskan.

| ID | Pertanyaan | Keputusan | Sumber |
| --- | --- | --- | --- |
| D-01 | Job `paid` tidak diambil siapa pun | Setelah **7 hari** sejak masuk status `paid`, job otomatis `cancelled`, **refund 100%**, listing kembali `active`, pemohon dinotifikasi. Penghitung mulai ulang saat inspektor membatalkan dan job kembali ke `paid` (kolom `available_since`) | [TIM] 7 hari, rincian [USULAN] |
| D-02 | Pembelian saat listing `in_inspection` | **Boleh.** Pembeli dapat mengajukan beli dan penjual dapat menyetujui. **Pembayaran** (dana ditahan) baru dibuka setelah tidak ada job aktif, sehingga status listing tetap satu nilai dan hasil inspeksi sudah ada sebelum uang pembeli ditahan | [TIM] boleh, mekanisme [USULAN] |
| D-03 | Pemohon tidak menekan "Terima Hasil" | Job `result_submitted` otomatis `completed` setelah **3 hari** (`auto_accept_days`, dapat diubah admin) dan fee inspektor cair | [TIM] setuju, 3 hari [USULAN] |
| D-04 | Pembatalan transaksi | Lihat tabel di bawah | [TIM] "paling logis", rincian [USULAN] |
| D-05 | Edit listing aktif | Field **non-inti** (harga, nego, deskripsi, foto, warna, slot dan tempat inspeksi) berlaku langsung. Field **inti** (jenis, merek, model, tahun, nomor rangka, kilometer) membuat listing kembali ke `pending_review` dan **lencana dicabut** (`is_inspected = false`). Hasil lama tetap ada di riwayat pembayar. Lencana kembali setelah inspeksi baru selesai. Field inti terkunci selama ada job aktif atau transaksi | [USULAN] |
| D-06 | Inspektor membeli, menjual, atau mengajukan inspeksi | Tetap **dilarang** pada akun inspektor. Inspektor yang ingin membeli membuat akun pengguna terpisah (email berbeda) | [OPEN], default PRD |

## D-04. Pembatalan transaksi

| Kondisi | Siapa | Aturan | Dana | Listing |
| --- | --- | --- | --- | --- |
| `offered` (belum disetujui) | Pembeli | Batal bebas | - | tetap |
| `offered` | Penjual | Tolak (`rejected`) | - | tetap |
| `accepted` (belum bayar) | Pembeli atau penjual | Batal bebas, dicatat | - | kembali ke status sebelum transaksi |
| `paid_held` | Penjual | Batal sepihak diperbolehkan, dicatat untuk tinjauan admin | Refund 100% ke pembeli | `active` |
| `paid_held` | Pembeli | Perlu persetujuan penjual **atau** admin | Refund 100% ke pembeli | `active` |
| `paid_held` | Admin | Dapat membatalkan atau menyelesaikan manual | Sesuai keputusan admin | sesuai |
| Semua pembatalan | | Komisi inspektor 3% **tidak** dibayar | | |

Kedaluwarsa [USULAN]:

| Status | Batas | Hasil |
| --- | --- | --- |
| `offered` | 48 jam tanpa tanggapan penjual | `expired` |
| `accepted` | 24 jam tanpa pembayaran, dihitung sejak pembayaran **dibuka** (tidak ada job aktif) | `expired` |
| `paid_held` | 7 hari tanpa konfirmasi serah terima | Ditandai "perlu tindakan" untuk admin (tidak otomatis) |

Satu listing hanya boleh memiliki satu transaksi berjalan (`offered`, `accepted`, `paid_held`). Karena itu kedaluwarsa wajib ada supaya penawaran basi tidak mengunci listing.

## Dampak ke PRD

Perubahan berikut harus masuk PRD v1.6:

- 8.3: tambah aturan job `paid` 7 hari (D-01).
- 8.5 dan 14.2: tambah `result_submitted → completed` otomatis (D-03).
- 8.6, 13.12, 14.3: aturan pembatalan dan kedaluwarsa transaksi, serta pembayaran ditunda saat ada job aktif (D-02, D-04).
- 13.3 dan Open Question 13: aturan edit listing (D-05).
- 18.7: kolom `available_since` pada `inspection_jobs`.
- 18.17 `app_settings`: tambah `auto_accept_days` (3), `paid_job_timeout_days` (7).
- 22: edge case baru dari flow doc.
