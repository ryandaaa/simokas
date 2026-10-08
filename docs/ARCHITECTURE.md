# Arsitektur

Tujuan: setiap anggota tim bisa mengerjakan satu fitur tanpa mengganggu fitur lain. Karena itu kode dibagi menjadi **domain** yang berdiri sendiri, dan hubungan antar-domain dibatasi.

## 1. Struktur folder

```
app/
  Domains/
    <Domain>/
      Actions/            satu class = satu use case (logika bisnis)
      Contracts/          interface yang boleh dipakai domain lain
      DTOs/               objek data masukan/keluaran Action
      Enums/              status dan tipe, termasuk aturan transisi
      Events/             event yang dipancarkan domain ini
      Listeners/          reaksi terhadap event dari domain lain
      Http/
        Controllers/      tipis: validasi, panggil Action, kembalikan respons
        Requests/         FormRequest
        Resources/        bentuk JSON/respons (pemisah data publik dan privat)
      Livewire/           komponen UI domain ini
      Models/             model Eloquent (relasi, scope, cast; tanpa logika use case)
      Policies/           otorisasi
      Console/            command terjadwal milik domain
      Database/
        migrations/
        factories/
        seeders/
      Resources/views/    view Blade domain (namespace `<domain>::`)
      Routes/web.php      rute domain (api.php bila perlu)
      Tests/
        Unit/
        Feature/
      Providers/<Domain>ServiceProvider.php
  Support/                kode lintas domain yang tidak punya aturan bisnis
resources/views/components/ui/   komponen UI bersama (lihat UI_GUIDELINES)
tests/                    Pest.php, TestCase, arch test, helper bersama
docs/
```

Setiap domain mendaftarkan sendiri rute, migrasi, view, policy, event listener, dan komponen Livewire di `<Domain>ServiceProvider`. `bootstrap/providers.php` hanya berisi daftar provider domain. Menambah fitur berarti menambah folder dan satu baris provider, bukan mengubah file inti.

## 2. Daftar domain

| Domain | Tanggung jawab | Flow |
| --- | --- | --- |
| Identity | Registrasi, login, profil, role, blokir | [auth](flows/auth.md) |
| Inspector | Profil inspektor, dokumen, status verifikasi | [onboarding](flows/onboarding.md) |
| Listing | Penawaran kendaraan, foto, slot inspeksi, status listing | [listing](flows/listing.md) |
| Catalog | Pencarian, filter, tampilan publik (baca saja) | [catalog](flows/catalog.md) |
| Inspection | Job inspeksi dan state machine job | [job](flows/job.md) |
| Result | Template, hasil, foto, skor, akses hasil | [result](flows/result.md) |
| Payment | Tagihan, pembayaran simulasi, ledger, refund | [payment](flows/payment.md) |
| Transaction | Jual beli di platform (P1) | [transaction](flows/transaction.md) |
| Chat | Percakapan dan pesan (P1) | [chat](flows/chat.md) |
| Rating | Rating inspektor (P1) | [rating](flows/rating.md) |
| Notification | Notifikasi dalam web (P1) | [notification](flows/notification.md) |
| Setting | Tarif, komisi, parameter waktu | [admin](flows/admin.md) |
| Admin | Antrean dan aksi moderasi (tipis, memakai Action domain lain) | [admin](flows/admin.md) |
| Dashboard | Ringkasan per role (baca saja) | [dashboard](flows/dashboard.md) |

`App\Support` berisi `Money` (aturan pembagian dan pembulatan), `Clock` (waktu UTC dan WIB), dan trait atau helper kecil. Isinya tidak boleh bergantung pada domain mana pun.

## 3. Aturan antar-domain

Ini aturan paling penting. Dua orang yang mengerjakan dua domain berbeda tidak boleh saling merusak.

| # | Aturan |
| --- | --- |
| A1 | Domain **boleh membaca** Model domain lain (relasi, `with`, query baca) |
| A2 | Domain **tidak boleh mengubah** data domain lain secara langsung (`save`, `update`, `create`). Gunakan Action publik domain itu, kontrak, atau event |
| A3 | Domain **tidak boleh** memakai `Livewire`, `Http`, `Listeners`, dan `Actions` internal domain lain. Yang boleh dipakai lintas domain hanya `Contracts`, `Events`, `Enums`, dan `DTOs` yang ditandai publik |
| A4 | Pemanggilan sinkron lintas domain memakai **Contract** (interface di `Contracts/`, binding di ServiceProvider pemilik). Contoh: `Payment\Contracts\Billing` dipanggil Inspection |
| A5 | Reaksi yang tidak harus sinkron memakai **Event**. Pemancar tidak tahu siapa pendengarnya |
| A6 | Migrasi milik domain pemilik tabel. Foreign key lintas domain diperbolehkan, tetapi tabel milik domain lain tidak diubah |
| A7 | View bersama hanya komponen di `resources/views/components/ui`. Komponen khusus domain tetap di domain |
| A8 | Dilarang memakai `Support` untuk menyimpan logika bisnis. Jika berhubungan dengan aturan produk, taruh di domain |

Aturan A2 dan A3 dijaga oleh **arch test** Pest (lihat [TESTING.md](TESTING.md)), sehingga pelanggaran membuat CI gagal.

## 4. Pola per lapisan

| Lapisan | Boleh | Tidak boleh |
| --- | --- | --- |
| Controller / Livewire | Validasi input, `authorize`, panggil satu Action, kembalikan respons | Query kompleks, hitung uang, ubah status langsung |
| Action | Logika bisnis, transaksi DB, kunci baris, memancarkan event | Mengakses `request()` atau sesi, membangun HTML |
| Model | Relasi, scope, cast, accessor ringan | Logika use case, memanggil Action |
| Policy | Aturan siapa boleh melakukan apa | Mengubah data |
| Enum status | Daftar status, label, `canTransitionTo()` | Efek samping |
| Resource | Memilih field yang boleh keluar (publik atau privat) | Query |

### Bentuk Action

```php
final class TakeJob
{
    public function __construct(private readonly Dispatcher $events) {}

    public function handle(User $inspector, InspectionJob $job): InspectionJob
    {
        return DB::transaction(function () use ($inspector, $job) {
            $affected = InspectionJob::query()
                ->whereKey($job->getKey())
                ->where('status', JobStatus::Paid)
                ->whereNull('inspector_id')
                ->update(['inspector_id' => $inspector->getKey(), 'status' => JobStatus::Taken, 'taken_at' => now()]);

            if ($affected === 0) {
                throw new JobAlreadyTaken();
            }

            $this->events->dispatch(new JobTaken($job->refresh()));

            return $job;
        });
    }
}
```

Satu Action = satu method publik `handle()`. Action dipanggil dari Livewire, controller, command, dan test dengan cara yang sama.

## 5. State machine

Status disimpan sebagai string, dimodelkan sebagai **Enum PHP**. Setiap enum punya `canTransitionTo(self $to): bool` dan daftar transisi yang sah. Perubahan status **hanya** lewat Action domain pemiliknya. Perubahan status yang tidak sah melempar exception domain, bukan mengubah diam-diam.

Lima state machine: listing, job inspeksi, pembayaran, transaksi, verifikasi inspektor. Diagramnya ada di flow doc masing-masing. Setiap transisi wajib ada test.

## 6. Data persis dan privasi

- Koordinat, alamat lengkap, dan nomor rangka hanya keluar lewat `Resource` privat yang diotorisasi Policy. Resource publik tidak punya field itu sama sekali.
- Ada arch test dan feature test yang memastikan respons publik tidak memuat field tersebut.
- File inspeksi dan dokumen inspektor memakai disk privat dan tautan bertanda tangan sementara.

## 7. Operasi uang, status, dan konkurensi

- Semua operasi uang dan transisi status berada dalam `DB::transaction`.
- Rebutan (ambil job, pembayaran ganda) dijaga dua lapis: pembaruan berkondisi atau `lockForUpdate()` di Action, dan batasan unik di database sebagai pengaman terakhir.
- Pembayaran memakai `idempotency_key` unik.
- Pembagian uang hanya lewat `Support\Money::split()`. Tidak boleh ada perhitungan komisi di tempat lain.

## 8. Tugas terjadwal

Laravel Scheduler dijalankan cron hPanel setiap menit (`php artisan schedule:run`). Tugas dimiliki domain pemicunya dan didaftarkan di ServiceProvider domain itu.

| Tugas | Domain | Periode |
| --- | --- | --- |
| Kedaluwarsakan tagihan > 30 menit | Inspection | Tiap menit |
| Batalkan dan refund job `paid` yang tidak diambil 7 hari | Inspection | Harian |
| Otomatis selesaikan job `result_submitted` setelah 3 hari | Inspection | Harian |
| Tandai job "perlu tindakan" bila jadwal belum ditetapkan 48 jam | Inspection | Tiap jam |
| Kedaluwarsakan penawaran beli `offered` 48 jam | Transaction | Tiap menit |
| Kedaluwarsakan persetujuan `accepted` 24 jam tanpa bayar | Transaction | Tiap menit |
| Tandai transaksi `paid_held` 7 hari tanpa serah terima | Transaction | Harian |

Antrean memakai driver `database` atau dijalankan sinkron. Proses yang harus terus hidup tidak diandalkan di hosting.

## 9. Menambah fitur atau domain baru

1. Salin [`features/_TEMPLATE.md`](features/_TEMPLATE.md) dan tulis spesifikasinya. Tambahkan atau perbarui flow doc.
2. Buat folder `app/Domains/<Nama>` dengan struktur di atas dan ServiceProvider.
3. Daftarkan provider di `bootstrap/providers.php`.
4. Tulis test (lihat [TESTING.md](TESTING.md)) bersamaan dengan kode.
5. Jalankan `composer check` sebelum membuka PR.

Fitur baru tidak boleh mengharuskan perubahan pada domain lain, kecuali menambah event atau kontrak. Bila terpaksa, perubahan itu dipisah menjadi PR tersendiri dan didiskusikan dulu.

## 10. Yang sengaja tidak dipakai

- Paket modul pihak ketiga (nwidart): menambah dependensi tanpa kebutuhan nyata.
- Repository pattern di atas Eloquent: Eloquent sudah cukup, Action yang menjadi batas.
- Proses WebSocket sendiri (Reverb): tidak dapat diandalkan di hosting saat ini.
- Microservice atau multi-tenant: di luar lingkup, dicatat di roadmap SaaS PRD.
