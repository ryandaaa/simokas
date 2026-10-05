# Fitur: UI Kit & Komponen Bersama (F2)

| | |
| --- | --- |
| **Domain** | Foundation (`shared`) |
| **Prioritas** | P0 |
| **Status** | Selesai |
| **Referensi PRD** | Bagian 20 (NFR) & Bagian 21 (Arah Desain) |
| **Panduan UI** | `docs/UI_GUIDELINES.md` |

## 1. Ringkasan

Menyediakan seluruh kontrak komponen UI bersama (`<x-ui.*>`), layout aplikasi responsif (bilah atas desktop dan menu bawah mobile), token desain di `resources/css/app.css` (`@theme`), font self-hosted Plus Jakarta Sans, set ikon Heroicons inline, halaman error ramah berbahasa Indonesia, terjemahan `lang/id`, serta galeri komponen internal di `/_ui`.

Komponen dibangun dengan pendekatan **F2 polos**: fungsional, semantik, mobile-first, tanpa dekorasi berlebihan. Semua kartu fitur berikutnya diwajibkan hanya menggunakan komponen ini.

## 2. Token Desain

Didefinisikan di `resources/css/app.css` melalui direktif Tailwind CSS v4 `@theme`. Dilarang menulis kode warna heksadesimal (`#...`) atau `style=""` pada view Blade atau Livewire.

| Token | Nilai | Penggunaan |
| --- | --- | --- |
| `--color-primary` | `#12355B` | Tombol utama, header, tautan |
| `--color-accent` | `#F59E0B` | Penekanan, lencana inspeksi |
| `--color-success` | `#16A34A` | Layak, berhasil |
| `--color-warning` | `#D97706` | Perlu perhatian, menunggu |
| `--color-danger` | `#DC2626` | Tidak direkomendasikan, galat |
| `--color-bg` | `#F8FAFC` | Latar belakang halaman |
| `--color-surface` | `#FFFFFF` | Permukaan kartu, modal |
| `--color-text` | `#0F172A` | Teks utama |
| `--color-muted` | `#64748B` | Teks pendukung |

## 3. Kesepakatan Enum Status Domain [USULAN]

Komponen `<x-ui.status-chip>` hanya menerima props primitif: `label` (string), `variant` ('success'|'warning'|'danger'|'primary'|'muted'), dan `icon` (string).

**Kesepakatan bersama untuk semua domain:**
Setiap kali sebuah domain memperkenalkan Enum status (misalnya `ListingStatus`, `JobStatus`, `InspectionRecommendation`), Enum tersebut harus mengimplementasikan tiga method:
1. `label(): string` — mengembalikan teks ramah pengguna (mengambil dari file bahasa `lang/id/`)
2. `color(): string` — mengembalikan varian warna ('success', 'warning', 'danger', 'primary', 'muted')
3. `icon(): string` — mengembalikan nama ikon di `<x-ui.icon>`

Di view domain, pemanggilan status cukup dilakukan dengan:
```blade
<x-ui.status-chip :label="$status->label()" :variant="$status->color()" :icon="$status->icon()" />
```
Sehingga view domain tidak menulis ulang aturan warna atau logika pemetaan status.

## 4. Spesifikasi Kontrak Komponen UI

### 4.1 `<x-layouts.app>`
Layout aplikasi utama responsif (mobile-first).
- **Props:**
  - `title` (string|null): Judul halaman (otomatis digabung nama aplikasi).
  - `navItems` (array): Daftar item navigasi `[['label' => '...', 'url' => '...', 'icon' => '...', 'active' => bool]]`. Maksimal 5 item ditampilkan di menu bawah HP.
  - `headerActions` (slot|null): Aksi di kanan header desktop.
  - `notifications` (slot|null): Kontainer notifikasi visual dan `aria-live`.
- **Fitur:** Skip-link ke `#main-content`, `aria-live` region terintegrasi, bilah atas desktop, menu navigasi bawah di HP (tinggi sentuh min 44px), serta pemuatan `@livewireStyles` dan `@livewireScripts` tepat satu kali sehingga Alpine.js tersedia di seluruh halaman (termasuk halaman tanpa komponen Livewire dan halaman galeri/error).

### 4.2 `<x-ui.button>`
Tombol interaktif dan tautan berpenampilan tombol.
- **Props:**
  - `variant` ('primary'|'secondary'|'danger'|'ghost', bawaan 'primary').
  - `type` ('button'|'submit'|'reset', bawaan 'button').
  - `href` (string|null): Bila diisi, komponen merender tag `<a>`.
  - `disabled` (bool, bawaan false).
  - `loading` (bool, bawaan false): Menampilkan spinner dan menonaktifkan klik.
  - `icon` (string|null): Nama ikon sisi kiri.
  - `iconRight` (string|null): Nama ikon sisi kanan.
- **Fitur:** Tinggi minimal 44 px, `wire:loading.attr="disabled"` bawaan untuk mencegah klik ganda di Livewire.

### 4.3 `<x-ui.input>`
Field teks beraksesibilitas penuh.
- **Props:**
  - `id` (string|null), `name` (string|null), `type` (string, bawaan 'text').
  - `label` (string|null): Label terlihat terhubung atribut `for`.
  - `hint` (string|null): Teks bantuan terhubung via `aria-describedby`.
  - `error` (string|null): Pesan galat eksplisit atau otomatis dari `$errors`.
  - `required` (bool), `disabled` (bool), `loading` (bool).
- **Fitur:** Status galat mengaktifkan `aria-invalid="true"` dan `role="alert"`, dukungan keadaan skeleton loading.

### 4.4 `<x-ui.select>`
Menu pilihan dropdown.
- **Props:**
  - `id`, `name`, `label`, `hint`, `error`, `required`, `disabled`, `loading`.
  - `options` (array): Array opsi `['value' => 'Label']` atau array objek `[['value' => '...', 'label' => '...']]`.
  - `placeholder` (string|null), `selected` (string|null).
  - Atau mengisi via slot `<option>`.

### 4.5 `<x-ui.textarea>`
Area input teks multiline.
- **Props:** `id`, `name`, `label`, `hint`, `error`, `rows` (int, bawaan 4), `required`, `disabled`, `loading`.

### 4.6 `<x-ui.checkbox>`
Kotak centang ramah sentuhan.
- **Props:** `id`, `name`, `label`, `hint`, `error`, `value` (string, bawaan '1'), `checked` (bool), `required`, `disabled`, `loading`.
- **Fitur:** Area sentuh minimal 44 px, terhubung dengan pembaca layar.

### 4.7 `<x-ui.card>`
Pembungkus konten semantik.
- **Props:**
  - `title` (string|null), `subtitle` (string|null).
  - `header` (slot|null), `footer` (slot|null).
  - `clickable` (bool), `href` (string|null).
  - `padding` (string, bawaan 'p-5 sm:p-6').
  - `loading` (bool).

### 4.8 `<x-ui.listing-card>`
Kartu listing kendaraan untuk halaman penjelajahan.
- **Props:**
  - `title` (string), `price` (int|string), `year` (int|string|null), `mileage` (int|string|null), `area` (string|null).
  - `imageUrl` (string|null), `imageAlt` (string|null).
  - `inspected` (bool), `inspectedDate` (string|Carbon|null).
  - `statusLabel` (string|null), `statusVariant` (string|null), `statusIcon` (string|null).
  - `href` (string|null), `loading` (bool).
- **Fitur:** Foto lazy-loading dengan dimensi width/height, pemformatan harga otomatis via `Money`.

### 4.9 `<x-ui.badge-inspected>`
Lencana "Sudah Diinspeksi".
- **Props:** `date` (string|Carbon|null), `compact` (bool, bawaan false).
- **Tampilan:** Ikon perisai centang solid berlatar warna accent.

### 4.10 `<x-ui.status-chip>`
Chip penanda status seragam (teks + ikon + warna).
- **Props:** `label` (string), `variant` ('success'|'warning'|'danger'|'primary'|'muted'), `icon` (string|null), `iconVariant` ('solid'|'outline', bawaan 'solid').

### 4.11 `<x-ui.stepper>`
Tahapan status proses / alur inspeksi.
- **Props:**
  - `steps` (array): Array langkah, baik array label sederhana atau array asosiatif `[['label' => '...', 'description' => '...', 'status' => 'completed'|'current'|'upcoming'|'failed']]`.
  - `current` (int, 0-indexed): Penanda langkah aktif bila memakai array label sederhana.
  - `orientation` ('horizontal'|'vertical', bawaan 'horizontal').

### 4.12 `<x-ui.empty-state>`
Tampilan keadaan kosong.
- **Props:** `icon` (string, bawaan 'dokumen'), `title` (string|null), `description` (string|null), `actionLabel` (string|null), `actionUrl` (string|null), `actionIcon` (string|null), `actionClick` (string|null).

### 4.13 `<x-ui.alert>`
Pesan pengumuman status / galat.
- **Props:** `variant` ('info'|'success'|'warning'|'danger'), `title` (string|null), `message` (string|null), `icon` (string|null), `dismissible` (bool), `retryLabel` (string|null), `retryHref` (string|null), `retryAction` (string|null).
- **Aksesibilitas:** `role="alert"` untuk varian danger/warning; `role="status"` untuk info/success.

### 4.14 `<x-ui.modal>`
Dialog konfirmasi tindakan tak dapat dibatalkan.
- **Props:** `name` / `id` (string), `show` (bool), `title` (string|null), `description` (string|null), `confirmText` (string|null), `confirmVariant` (string, bawaan 'primary'), `cancelText` (string|null), `maxWidth` (string).
- **Fitur:** Menjebak fokus (`x-trap`), mengembalikan fokus ke elemen pemicu saat ditutup, menutup dengan tombol `Esc`, tombol X, tombol Batal, atau klik di luar (`@click.outside`), serta dilindungi `x-cloak` agar tidak muncul saat awal sebelum Alpine siap.

### 4.15 `<x-ui.money>`
Pemformat mata uang Rupiah.
- **Props:** `amount` atau `value` (int|string|null).
- **Format:** Menggunakan `App\Support\Money::format()`, contoh: `Rp50.000`.

### 4.16 `<x-ui.datetime>`
Pemformat waktu WIB.
- **Props:** `value` atau `date` (DateTimeInterface|string|Carbon|null), `format` ('wib'|'date'|'time'|'relative', bawaan 'wib'), `showIcon` (bool).
- **Format:** Menggunakan `App\Support\Clock::formatWib()`, contoh: `5 Okt 2026, 14.30 WIB`.

### 4.17 `<x-ui.score-ring>`
Skor melingkar hasil inspeksi.
- **Props:** `score` (int|float), `max` (int, bawaan 100), `recommendation` ('recommended'|'caution'|'not_recommended'|string|null), `label` (string|null), `loading` (bool).
- **Fitur:** Progress ring SVG, status chip rekomendasi terintegrasi.

### 4.18 `<x-ui.camera-capture>`
Kerangka UI tangkapan kamera inspeksi.
- **Props:** `label` (string|null), `jobId` (string|null), `timestamp` (string|Carbon|null), `photoUrl` (string|null), `permissionDenied` (bool), `loading` (bool).
- **Fitur:** Mode siap ambil (akses kamera lingkungan), mode pratinjau dengan cap waktu dan tombol "Ulangi" / "Pakai foto ini", dan mode penanganan izin ditolak.

### 4.19 `<x-ui.pagination>`
Navigasi halaman daftar.
- **Props:** `paginator` (Paginator contract) atau primitif (`currentPage`, `totalPages`, `hasPrevious`, `hasNext`, `previousUrl`, `nextUrl`, `total`).

### 4.20 `<x-ui.skeleton>`
Komponen kerangka pemuatan (placeholder).
- **Props:** `variant` ('text'|'title'|'card'|'avatar'|'button'|'image'|'badge'|'circle').

### 4.21 `<x-ui.icon>`
Ikon SVG Heroicons inline.
- **Props:** `name` (string), `variant` ('outline'|'solid', bawaan 'outline'). Mendukung alias Indonesia dan Inggris.
