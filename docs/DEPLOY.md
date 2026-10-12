# Deploy SIMOKAS ke Hostinger (Cloud Startup)

Panduan deploy produksi untuk lomba. Kartu: **F4**.

> Deploy **otomatis** via GitHub Actions (`.github/workflows/deploy.yml`):
> push ke `main` → CI hijau → build aset di runner → kirim via SCP →
> backup DB → `git reset --hard` → migrate → cache → health check.
> Bagian manual (§2 lama, ZIP + timpa file manager) **tidak dipakai lagi**,
> disimpan sebagai fallback di akhir dokumen.

## 0. Batasan keras (jangan dilanggar)

- Akun Hostinger ini menampung **banyak project produksi milik tim**. SIMOKAS dipasang sebagai **website baru terpisah** (subdomain temporer `*.hostingersite.com` untuk staging/demo).
- Jangan mengubah, menghapus, atau memakai ulang: folder website lain, database/user database lain, cron job lain, dan versi PHP website lain (PHP dipilih per website di hPanel).
- Isolasi SIMOKAS: **folder sendiri + database + user DB sendiri + cron sendiri + `.env` sendiri**.

## 1. Prasyarat di hPanel (verifikasi sekali)

| Cek | Cara | Syarat |
| --- | --- | --- |
| PHP | Websites → Dashboard website SIMOKAS → PHP version | **8.3** (sama dengan `composer.json`) |
| SSH | hPanel → SSH Access | Bisa login, `php -v` = 8.3, `composer --version` jalan |
| MySQL | Databases → MySQL Databases | Buat **DB baru + user baru** khusus SIMOKAS. Butuh MySQL 8 (kolom virtual berindeks unik, lihat CONVENTIONS §3) |
| Pusher | — | Koneksi keluar ke Pusher diizinkan (chat/notifikasi realtime). Tanpa ini, UI otomatis fallback ke polling |
| Cron | Websites → Dashboard → Cron Jobs | Bisa tambah cron custom. Paket Cloud Startup = cron unlimited. **Jadwal cron memakai UTC+0** (WIB = UTC+7) |

## 1b. GitHub Secrets & Variables (wajib sekali)

Repo → Settings → Secrets and variables → Actions:

| Nama | Jenis | Isi |
| --- | --- | --- |
| `HOSTINGER_HOST` | Secret | Host SSH dari hPanel → SSH Access |
| `HOSTINGER_USER` | Secret | Username SSH (mis. `u784310550`) |
| `HOSTINGER_PORT` | Secret | Port SSH (shared hosting umumnya `65002`) |
| `HOSTINGER_KEY` | Secret | **Private key** ed25519 (termasuk baris `BEGIN/END`); public key-nya ditempel di hPanel → SSH Access |
| `VITE_PUSHER_APP_KEY` | Variable | Key publik Pusher (dibakar ke JS saat build; tanpanya realtime mati, UI fallback polling) |
| `VITE_PUSHER_APP_CLUSTER` | Variable | Cluster Pusher (mis. `ap1`) |

Jangan pernah menempel private key/password ke chat atau commit.

## 2. Alur otomatis (utama) vs paket manual (fallback)

**Utama — otomatis:** cukup `git push origin main`. CI berjalan (±5 mnt);
bila hijau, workflow deploy jalan sendiri: arsip kode (`git archive`, tanpa git
di server) + build aset di runner (server tidak punya Node) → kirim keduanya
via SCP → backup DB berstempel waktu (simpan 7 terakhir di
`domains/xxxx.hostingersite.com/backups/`, di luar docroot) → ekstrak kode →
salin `public/` ke docroot + ekstrak build → `composer install --no-dev` →
`migrate --force` → `config/route/view/event:cache` → `queue:restart` →
health check homepage + 1 aset CSS (`curl -f`, gagal = merah).

**Fallback — paket manual (bila Actions down):**

1. `composer install --no-dev --prefer-dist --optimize-autoloader` sudah teruji lokal (CI hijau).
2. `npm run build` lokal sampai `public/build/manifest.json` terbentuk. Hasil build **wajib diupload** (folder `public/build` gitignored).
3. Buat ZIP berisi seluruh repo **KECUALI**: `node_modules/`, `.git/`, `.env` (lokal!), `public/hot`, `tests/` (opsional, boleh skip untuk hemat), `storage/logs/*`.
4. `vendor/` boleh ikut di-ZIP **atau** install ulang di server via SSH (`composer install --no-dev`). Pilih salah satu, jangan dua-duanya (hemat waktu upload).

## 3. Upload dan struktur folder (dikerjakan di hPanel/SSH)

1. Di hPanel: **+ Buat website** → pilih subdomain temporer → dapat folder domain, misal `domains/xxxx.hostingersite.com/`.
2. Upload ZIP ke folder domain, ekstrak sehingga menjadi:
   ```
   domains/xxxx.hostingersite.com/
     simokas/          <- root Laravel (app/, routes/, .env, artisan, ...)
       public/         <- docroot
   ```
   Alternatif sesuai docs Hostinger: taruh kode 1 level di atas `public_html` bila memakai domain utama. Untuk subdomain temporer, pakai struktur di atas.
3. Arahkan **document root** website ke `.../simokas/public/` (pengaturan domain di hPanel).
4. Permission: folder `755`, file `644`; pastikan `storage/` dan `bootstrap/cache/` writable oleh PHP.

## 4. Konfigurasi `.env` produksi (buat baru di server, JANGAN upload `.env` lokal)

```ini
APP_NAME=SIMOKAS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://xxxx.hostingersite.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<db_baru_simokas>
DB_USERNAME=<user_baru_simokas>
DB_PASSWORD=<password_baru>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

ADMIN_EMAIL=<email admin asli>
ADMIN_PASSWORD=<min 12 karakter, kuat, bukan placeholder>

SEED_DEMO=false

BROADCAST_CONNECTION=pusher   # atau log bila Pusher belum siap (fallback polling)
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=
VITE_PUSHER_APP_KEY=
VITE_PUSHER_APP_CLUSTER=
```

Lalu via SSH di folder `simokas/`:

```bash
php artisan key:generate
php artisan storage:link
php artisan migrate --force --seed
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Catatan seed: `DatabaseSeeder` selalu menjalankan `ReferenceSeeder` (tarif, template, admin) dan **melewati `DemoSeeder`** bila `APP_ENV=production` — listing = 0, akun demo = tidak ada. Jangan pernah set `SEED_DEMO=true` di produksi.

Pastikan `public/hot` **tidak ada** di server (file itu hanya untuk Vite dev lokal; keberadaannya merusak pemuatan aset produksi).

## 5. Cron (hPanel → Cron Jobs, website SIMOKAS saja)

Binary PHP yang terbukti jalan di server ini: `/opt/alt/php83/usr/bin/php`
(gunakan ini di cron, bukan `/usr/bin/php`).

| # | Jadwal | Perintah | Fungsi |
| --- | --- | --- | --- |
| 1 | Tiap menit `* * * * *` | `cd /home/<user>/domains/xxxx.hostingersite.com/simokas && /usr/bin/php artisan schedule:run >> /dev/null 2>&1` | 7 tugas terjadwal (expire tagihan 30 mnt, expire offered/accepted, cancel job 7 hari, auto-complete 3 hari, tandai perlu tindakan). Daftar: `php artisan schedule:list` |
| 2 | Tiap menit `* * * * *` | `cd /home/<user>/domains/xxxx.hostingersite.com/simokas && /usr/bin/php artisan queue:work --stop-when-empty --tries=3 --timeout=60 >> /dev/null 2>&1` | Proses antrean broadcast Pusher (chat/notifikasi realtime). Tanpa ini antrean menumpuk dan realtime mati (UI fallback polling) |

Jangan jalankan `queue:work` daemon persisten (tidak didukung hosting ini). Jangan sentuh cron milik website lain.

## 6. Verifikasi pasca-deploy

- [ ] `https://xxxx.hostingersite.com` tampil dengan CSS penuh (bukan HTML mentah)
- [ ] Login admin (kredensial `ADMIN_*`), dashboard admin terbuka
- [ ] Buat 1 listing uji → validasi admin → ajukan inspeksi → bayar simulasi → job muncul di daftar inspektor
- [ ] `php artisan schedule:list` menampilkan 7 tugas; tunggu 2 menit, cek tidak ada error di `storage/logs/laravel.log`
- [ ] Daftar antrean `jobs` diproses (cek tabel `jobs` kosong setelah aksi realtime)
- [ ] Chat/notifikasi: kirim pesan uji; bila Pusher belum dikonfigurasi, polling cadangan harus tetap menampilkan pesan
- [ ] Hapus listing uji + akun uji bila ada sebelum dinilai juri

## 7. Pindah ke domain final (bila lomba memakai domain sendiri)

1. Tambahkan domain sebagai website baru (atau ganti domain website ini) — tetap folder/DB/cron terpisah.
2. Update `APP_URL` dan `VITE_*` (Variables GitHub bila berubah), push ke `main` — build + upload aset otomatis.
3. Verifikasi §6 dari awal (health check workflow sudah mencakup homepage + aset).

## 8. Rollback darurat (opsional untuk demo, boleh skip)

Staging ini hanya untuk demo, jadi maintenance mode tidak wajib. Bila butuh:

```bash
php artisan down --render="errors.503"   # maintenance mode
# perbaiki, lalu:
php artisan up
```

DB: sebelum `migrate:fresh` apapun di produksi, ekspor backup via hPanel → Databases → Export.
