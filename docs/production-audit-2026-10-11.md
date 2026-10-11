# Audit SIMOKAS: 11 Oktober 2026

## Ringkasan

Audit menemukan penyebab tombol **Lanjutkan** tidak mengirim request: `@vite` tercetak sebelum root Blade Livewire, lalu Livewire menempelkan `wire:id` ke tag `<link>` pertama. Akibatnya tombol berada di luar root komponen; browser memang tidak membuat request Network. Asset kini dibungkus `@assets`, yang Livewire injeksikan ke `<head>` tanpa mengubah root. Paket koreksi belum dikirim ke production.

Pemeriksaan health endpoint production (`/up`) memberi HTTP 200. Alur yang memerlukan login tidak diuji di production. GPS tetap diminta setelah pengguna menekan **Gunakan lokasi**; browser mewajibkan konteks aman (HTTPS) dan persetujuan pengguna, sehingga aplikasi tidak meminta lokasi diam-diam saat halaman dibuka.

## Temuan dan status

| Prioritas | Temuan | Tindakan / status |
| --- | --- | --- |
| Kritis | `@vite` di awal view berada sebelum root Livewire. `wire:id` dipasang ke `<link>`, sehingga tombol `wire:click` tidak terdaftar dan tidak ada request Network. | Bundle dipindahkan ke `@assets` pada komponen create/edit; regresi memeriksa root component adalah `<div>` dan asset tetap ada di head. |
| Tinggi | Listener peta meneruskan objek event sebagai root DOM, menyebabkan `querySelectorAll is not a function` setelah navigasi/event. | Hook `morphed` menginisialisasi elemen sesudah perubahan DOM Livewire; callback `DOMContentLoaded` dan `livewire:navigated` tidak lagi meneruskan objek event. |
| Tinggi | Preview foto memakai URL signed Livewire; screenshot menunjukkan permintaan `preview-file` mendapat 401. | Create dan edit kini menampilkan object URL lokal dari browser; URL dilepas saat foto dihapus atau komponen dihancurkan. |
| Sedang | Geolocation bergantung pada HTTPS dan izin browser; halaman tidak boleh meminta GPS otomatis tanpa tindakan pengguna. | Tombol tetap menjadi tindakan eksplisit; pesan kondisi secure-context ditampilkan sebelum meminta lokasi. Uji izin/lokasi langsung di browser belum dapat dijalankan pada sesi ini. |
| Tinggi | Workflow deploy yang sudah ada bukan deploy atomik: workflow menghapus symlink `public` dan build lama sebelum langkah salin/symlink selesai; tidak ada pemulihan otomatis bila deploy gagal. Pemeriksaan `curl` hanya mencetak status, sehingga respons HTTP 5xx belum tentu menggagalkan job. | Belum diubah. File workflow sudah ada sebagai file untracked milik workspace; perlu ditinjau dan diperkeras sebelum mengandalkannya untuk rollback/availability production. |
| Sedang | Action deploy menggunakan tag `appleboy/ssh-action@v1`, bukan commit SHA yang immutable. | Belum diubah; pin action ke SHA tepercaya dan jadwalkan pembaruan SHA secara terkontrol. |

## Perubahan kode

- [create-listing.blade.php](../app/Domains/Listing/Resources/views/livewire/create-listing.blade.php): preview foto lokal dan pengelolaan object URL.
- [edit-listing.blade.php](../app/Domains/Listing/Resources/views/livewire/edit-listing.blade.php): preview lokal untuk foto baru.
- [listing-map.js](../resources/js/listing-map.js): inisialisasi setelah morph Livewire, pemeriksaan secure context, dan pendaftaran handler yang aman.
- [location-picker.blade.php](../app/Domains/Listing/Resources/views/partials/location-picker.blade.php): bundle tidak lagi dicetak dari partial dinamis.
- [package-lock.json](../package-lock.json): memperbarui `concurrently` dan `shell-quote` untuk menutup advisori npm kritis.

## Verifikasi

- `composer check`: Pint dan PHPStan lulus; Pest **2.461 tes / 15.731 assertion** lulus.
- `npm run build`: berhasil.
- `composer audit --locked --no-interaction`: tidak ada advisori kerentanan.
- `npm audit`: 0 kerentanan setelah upgrade `concurrently` dan `shell-quote` yang kompatibel dengan rentang versi proyek.
- `git diff --check`: bersih.
- Tes regresi: `LST-T18` (preview create), `LST-T19` (picker peta), `LST-T20` (preview edit), `LST-T21` (listener), `LST-T22`–`LST-T24` (root Livewire create/edit), semuanya lulus.
- Production `GET /up`: HTTP 200. Browser smoke test dengan akun pengguna serta izin GPS belum dijalankan; perbaikan harus diverifikasi setelah deployment.

## Catatan penggunaan

Peta hanya muncul pada langkah lokasi. Pada screenshot terakhir, judul `fortuner` kurang dari batas minimum 10 karakter. Setelah perbaikan root dirilis, tombol akan mengirim request, tetapi validasi tetap menolak judul itu; gunakan judul seperti `Toyota Fortuner 2020`. Tombol GPS hanya meminta lokasi setelah diklik, pada HTTPS dan setelah izin browser diberikan. Setelah deploy, uji create/edit foto, perpindahan ke langkah lokasi, klik **Gunakan lokasi**, izin GPS (allow/deny), serta pemuatan tile peta pada browser HTTPS.
