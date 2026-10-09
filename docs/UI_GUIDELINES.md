# Aturan UI

Arah desain: **modern minimalis, bersih, terpercaya**. Foto kendaraan menjadi pusat perhatian. Pengguna beragam usia dan kemampuan teknologi, sehingga teks jelas dan tombol besar. Mengikuti PRD bagian 20 dan 21.

## 1. Prinsip

1. **Mobile-first.** Rancang untuk layar 360 px dulu, lalu perbesar.
2. **Jelas sebelum cantik.** Satu aksi utama per layar.
3. **Konsisten.** Pakai komponen bersama. Jangan membuat gaya baru di halaman.
4. **Selalu ada keadaan.** Setiap layar punya tampilan memuat, kosong, dan gagal.
5. **Bahasa sederhana.** Tanpa istilah teknis di layar pembeli.

## 2. Token desain

Semua warna dan ukuran didefinisikan sebagai **token** di `resources/css/app.css` (`@theme`). **Dilarang** menulis kode warna (hex), ukuran piksel bebas, atau `style=""` di Blade dan Livewire. Dengan token, mode gelap (P2) cukup menambah satu set nilai.

| Token | Nilai | Pemakaian |
| --- | --- | --- |
| `--color-forest` (`primary`) | `#163300` | Warna utama teks, aksen gelap, border sekunder |
| `--color-lime` (`accent`) | `#9fe870` | HANYA tombol aksi primer dan status aktif/sukses |
| `--color-lime-hover` (`accent-hover`) | `#8fd95c` | Status hover tombol aksi primer |
| `--color-spruce` (`success`) | `#054d28` | Hover tautan, indikator sukses |
| `--color-linen` | `#e2f6d5` | Hover tombol sekunder, latar lembut, status primary |
| `--color-signal` | `#0b4c72` | Informasi / pemberitahuan netral |
| `--color-alarm` (`danger`) | `#cb272f` | Galat, bahaya, status tidak direkomendasikan |
| `--color-warning` | `#D97706` | Peringatan, perlu perhatian, menunggu |
| `--color-charcoal` (`text`) | `#454745` | Teks isi (body text), label formulir |
| `--color-obs` | `#0e0f0c` | Judul tebal (headings) |
| `--color-pebble` (`muted`) | `#868685` | Teks sekunder, placeholder, border input |
| `--color-slate` | `#6a6c6a` | Teks sekunder, spesifikasi kendaraan |
| `--color-fog` (`border`) | `#e8ebe6` | Garis halus pembatas, latar tombol nonaktif |
| `--color-paper` (`bg`, `surface`) | `#ffffff` | Kanvas latar belakang dan permukaan kartu |

Kontras teks terhadap latar memenuhi WCAG AA (4,5:1 untuk teks biasa). Tombol primer memakai teks forest di atas latar lime.

### Tipografi

- Satu keluarga huruf: **Satoshi** (Indian Type Foundry, lisensi ITF FFL: berkas font tidak boleh diubah), dihosting sendiri (tanpa CDN), dengan cadangan `system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif`.
- Bobot yang digunakan: Regular (400), Medium (500), Bold (700) dalam format berkas WOFF2 Latin lokal.
- Judul: tebal dengan pelacakan rapat (`font-bold tracking-tight text-obs`).
- Label formulir: 16 px (teks dasar) dengan bobot sedang (`text-base font-medium text-charcoal`).
- Ukuran dasar 16 px. Teks bantu minimal 14 px. Tidak ada teks di bawah 12 px.

### Jarak, sudut, bayangan

- Skala jarak Tailwind (kelipatan 4 px). Gutter halaman 16 px di HP.
- Bentuk tombol: pil bulat penuh (`rounded-full`), tinggi minimal 44 px, padding `px-6 py-2.5` (11 px 24 px).
- Tombol primer: latar lime dengan teks forest (hover `#8fd95c`).
- Tombol sekunder: latar putih dengan border forest (hover linen `#e2f6d5`).
- Tombol nonaktif: latar fog dengan teks pebble.
- Formulir input: tinggi 44 px (`h-11 min-h-[44px]`), sudut membulat 10 px (`rounded-[10px]`), border pebble, fokus border forest tanpa bayangan luar.
- Kartu: sudut membulat 10 px (`rounded-[10px]`), kontainer besar 16 px (`rounded-2xl`), tampilan datar (flat) tanpa gradien, garis batas halus fog (`border-fog`).
- Modal: sudut membulat 28 px (`rounded-[28px]`), backdrop forest transparan (`bg-forest/45`).

### Ikon

Heroicons (outline untuk umum, solid untuk status). Ikon yang membawa makna selalu disertai teks atau `aria-label`.

## 3. Komponen bersama

Dibuat sebagai Blade component di `resources/views/components/ui/` dan dipakai semua domain. Menambah komponen baru: buka PR terpisah, sertakan feature test render.

| Komponen | Catatan |
| --- | --- |
| `<x-ui.button>` | Varian `primary`, `secondary`, `danger`, `ghost`. Tinggi minimal 44 px. Status `loading` menonaktifkan klik ganda |
| `<x-ui.input>`, `select`, `textarea`, `checkbox` | Selalu dengan label terlihat, teks bantu, dan pesan galat |
| `<x-ui.card>` | Pembungkus konten |
| `<x-ui.dash-card>` | Pembungkus kartu dashboard dengan judul, subjudul, dan aksi |
| `<x-ui.stat-card>` | Kartu angka metrik kecil dengan angka besar dan label |
| `<x-ui.listing-card>` | Foto utama, judul, harga, tahun, kilometer, area, lencana |
| `<x-ui.badge-inspected>` | Lencana "Sudah Diinspeksi" dengan tanggal |
| `<x-ui.status-chip>` | Penanda status (lihat bagian 4) |
| `<x-ui.stepper>` | Tahapan status job |
| `<x-ui.empty-state>` | Ikon, judul, penjelasan, tombol tindakan |
| `<x-ui.alert>` | `info`, `success`, `warning`, `danger` |
| `<x-ui.modal>` | Konfirmasi tindakan tak dapat dibatalkan |
| `<x-ui.money>` | Format `Rp50.000` |
| `<x-ui.datetime>` | Format WIB |
| `<x-ui.score-ring>` | Skor melingkar dengan rekomendasi |
| `<x-ui.camera-capture>` | Komponen kamera (bagian 7) |
| `<x-ui.pagination>` | 12 item per halaman pada daftar listing |

## 4. Status dan penanda

Status tidak boleh hanya dibedakan lewat warna: selalu **teks + ikon + warna**.

| Status | Label | Warna | Ikon |
| --- | --- | --- | --- |
| Listing `pending_review` | Menunggu Validasi | warning | jam |
| Listing `active` | Aktif | success | centang |
| Listing `in_inspection` | Dalam Inspeksi | primary | kaca pembesar |
| Listing `in_transaction` | Dalam Transaksi | primary | dua panah |
| Listing `sold` | Terjual | muted | tag |
| Listing `rejected` | Ditolak | danger | silang |
| Listing `inactive` | Nonaktif | muted | jeda |
| Job `requested` | Menunggu Pembayaran | warning | jam |
| Job `paid` | Menunggu Inspektor | primary | jam pasir |
| Job `taken` | Diambil | primary | orang |
| Job `scheduled` | Dijadwalkan | primary | kalender |
| Job `in_progress` | Sedang Diinspeksi | primary | kaca pembesar |
| Job `result_submitted` | Menunggu Konfirmasi | warning | dokumen |
| Job `completed` | Selesai | success | centang |
| Job `cancelled` | Dibatalkan | muted | silang |
| Job `expired` | Kedaluwarsa | muted | jam |
| Rekomendasi `recommended` | Layak | success | centang |
| Rekomendasi `caution` | Perlu Perhatian | warning | segitiga |
| Rekomendasi `not_recommended` | Tidak Direkomendasikan | danger | silang |

Label job di tabel ini sengaja lebih ramah pembeli daripada label PRD 14.2 (misalnya `requested` = "Menunggu Pembayaran", bukan "Diajukan") [USULAN]. Kode status tetap sama dengan PRD.

Pemetaan status ke label, warna, dan ikon berada di satu tempat (method `label()`, `color()`, `icon()` pada Enum). Tidak ditulis ulang di view.

## 5. Tata letak dan responsivitas

- Titik henti Tailwind: `sm` 640, `md` 768, `lg` 1024.
- Daftar: **kartu** di HP, **tabel** mulai `md`. Jangan membuat tabel yang menggulir ke samping untuk data utama.
- Navigasi HP: menu bawah (maksimal 5 item) atau menu geser. Navigasi desktop: bilah atas.
- Aksi utama di HP diletakkan di bawah layar (mudah dijangkau satu tangan) pada formulir panjang.
- Tidak ada gulir horizontal pada halaman.
- Foto: thumbnail di daftar, versi penuh hanya di detail, `loading="lazy"`, dengan `width` dan `height` untuk mencegah loncatan tata letak.

## 6. Formulir

- Satu kolom di HP. Label di atas input, **bukan** placeholder sebagai pengganti label.
- Tandai wajib dan opsional dengan jelas.
- Validasi di server, pesan galat di bawah field terkait, berbahasa Indonesia, menyebut penyebab dan cara memperbaiki. Fokus pindah ke galat pertama.
- Jenis input yang tepat (`inputmode="numeric"` untuk harga dan kilometer, `type="tel"` untuk HP).
- Formulir panjang (listing, inspeksi) dipecah menjadi langkah atau kategori dengan indikator kemajuan dan **simpan draf**.
- Tombol kirim menampilkan status memuat dan mencegah klik ganda (`wire:loading.attr="disabled"`).
- Tindakan yang tidak dapat dibatalkan (bayar, kirim hasil, serah terima, batalkan) **selalu meminta konfirmasi** lewat modal yang menyebut akibatnya.

## 7. Kamera dan foto

- Foto inspeksi **hanya dari kamera**. Tidak ada tombol pilih dari galeri di formulir inspeksi.
- Komponen kamera memakai akses kamera browser (HTTPS), dengan cadangan input kamera bawaan HP.
- Tampilkan petunjuk jelas bila izin ditolak (cara mengaktifkan), dan jangan membuang isian lain.
- Foto menampilkan cap waktu dan ID job. Tampilkan pratinjau dengan tombol **Ulangi** dan **Pakai foto ini**.
- Foto listing dikompresi di sisi klien sebelum unggah. Tampilkan kemajuan unggah dan galat per foto.

## 8. Keadaan setiap layar

| Keadaan | Aturan |
| --- | --- |
| Memuat | Kerangka (*skeleton*) atau indikator. Tidak ada layar kosong putih |
| Kosong | `<x-ui.empty-state>` dengan penjelasan dan aksi berikutnya |
| Gagal | `<x-ui.alert variant="danger">` dengan penyebab, tombol **Coba lagi** |
| Tidak berhak | Halaman 403 ramah, tanpa membocorkan keberadaan data |
| Tidak ada koneksi | Pesan jelas. Pada formulir inspeksi, draf tetap aman |
| Real-time terputus | Indikator "menyambung ulang" pada chat |

Setiap komponen Livewire baru memiliki test untuk keadaan kosong dan gagal bila relevan.

## 9. Aksesibilitas

- Kontras WCAG AA, area sentuh minimal 44 x 44 px.
- Semua input punya `label` terhubung. Gambar bermakna punya `alt`, gambar dekoratif `alt=""`.
- Dapat dioperasikan dengan keyboard, fokus terlihat jelas, urutan tab logis.
- Modal menjebak fokus dan dapat ditutup dengan `Esc`.
- Pengumuman perubahan dinamis (galat, notifikasi baru) lewat `aria-live`.
- Target Lighthouse mobile: Performance ≥ 80, Accessibility ≥ 90 pada daftar barang dan detail listing.

## 10. Performa UI

- Tidak ada pustaka JS berat tanpa alasan. Livewire + Alpine sudah cukup.
- Leaflet dimuat hanya di halaman yang memakai peta.
- Gambar WebP bila memungkinkan, ukuran sesuai tampilan.
- Hindari N+1 pada daftar (*eager load*). Pagination 12 kartu per halaman.

## 11. Teks dan nada

- Bahasa Indonesia sederhana, ramah, tidak menggurui.
- Tombol memakai kata kerja jelas: **Ajukan Inspeksi**, **Bayar (Simulasi)**, **Kirim Hasil**.
- Halaman pembayaran simulasi diberi label **SIMULASI** yang terlihat jelas supaya tidak disangka uang sungguhan.
- Angka: `Rp50.000`, `62.000 km`, tanggal `5 Okt 2026`, jam `14.30 WIB`.

## 12. Daftar periksa UI sebelum PR

- [ ] Hanya memakai token dan komponen bersama, tanpa hex atau `style=""`.
- [ ] Diuji di lebar 360, 768, dan 1280 px.
- [ ] Keadaan memuat, kosong, dan gagal tersedia.
- [ ] Tindakan tak dapat dibatalkan meminta konfirmasi.
- [ ] Status memakai teks + ikon + warna.
- [ ] Label, kontras, fokus keyboard diperiksa.
- [ ] Teks UI ada di `lang/id`, bukan ditulis langsung.
