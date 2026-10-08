# Aturan Testing

Framework: **Pest**. Aturan utamanya satu kalimat: **tidak ada kode baru tanpa test dalam PR yang sama.**

## 1. Aturan wajib

| # | Aturan |
| --- | --- |
| T1 | Setiap **Action** baru punya **unit test** |
| T2 | Setiap **Policy**, **Enum status** (transisi), **Rule**, dan class logika murni (`Money`, penghitung skor) punya **unit test** |
| T3 | Setiap **rute**, **komponen Livewire**, **Listener**, dan **command terjadwal** baru punya **feature test** |
| T4 | Feature test mencakup **jalur sukses, otorisasi (setiap role yang tidak berhak ditolak), validasi gagal,** dan **edge case** yang tercantum di flow doc |
| T5 | Perbaikan bug dimulai dengan test yang gagal (reproduksi), baru perbaikannya |
| T6 | Setiap transisi status di state machine punya test sah dan test tidak sah |
| T7 | Setiap test case di flow doc (`<ID>-T..`) punya test dengan ID yang sama di nama test |
| T8 | PR yang mengubah perilaku wajib memperbarui flow doc dan test dalam PR yang sama |
| T9 | Tes tidak boleh dinonaktifkan, di-`skip`, atau diberi `todo` untuk membuat CI lulus. Test yang rapuh diperbaiki penyebabnya |

Reviewer menolak PR yang melanggar T1–T9.

## 2. Jenis test

| Jenis | Lokasi | Boleh | Tidak boleh |
| --- | --- | --- | --- |
| **Unit** | `app/Domains/<D>/Tests/Unit` | Logika murni, Policy dengan model di memori, Enum, Rule, **Action dengan `RefreshDatabase`** | Permintaan HTTP, render view |
| **Feature** | `app/Domains/<D>/Tests/Feature` | HTTP, Livewire (`Livewire::test`), Listener, command, database | Memanggil layanan eksternal sungguhan |
| **Arch** | `tests/Arch` | Aturan struktur antar-domain, larangan fungsi | Logika bisnis |
| **Lintas domain** | `tests/Feature/Journeys` | Satu alur end-to-end dari beberapa domain (lihat bagian 6) | Detail yang sudah diuji di domain |

Test milik domain tinggal di folder domain. Dengan begitu, orang yang mengerjakan satu domain hanya menyentuh folder itu.

## 3. Lingkungan test

- **Database test = MySQL/MariaDB**, bukan SQLite. Skema memakai kolom virtual dan indeks unik yang tidak berperilaku sama di SQLite. CI memakai service container MySQL 8. Versi yang dipakai produksi diverifikasi di hPanel [OPEN].
- Pakai `RefreshDatabase` (atau `DatabaseTransactions` bila memadai).
- Layanan eksternal di-*fake*: `Storage::fake()`, `Event::fake()` hanya bila test memang tentang event, `Bus::fake()`, `Http::fake()`. Pusher tidak pernah dipanggil di test.
- Pembayaran memakai modul simulasi asli. Tidak ada uang sungguhan, jadi tidak perlu di-mock.
- Waktu dibekukan (`travelTo`), bukan `sleep`.

## 4. Konvensi penulisan

```php
// app/Domains/Inspection/Tests/Feature/TakeJobTest.php
use App\Domains\Inspection\Models\InspectionJob;

it('JOB-T03 lets only one inspector take a paid job', function () {
    $job = InspectionJob::factory()->paid()->create();
    [$first, $second] = User::factory()->verifiedInspector()->count(2)->create();

    actingAs($first)->post(route('jobs.take', $job))->assertOk();
    actingAs($second)->post(route('jobs.take', $job))->assertStatus(409);

    expect($job->refresh()->inspector_id)->toBe($first->id);
});
```

- Nama test diawali **ID test case** dan berbahasa Inggris.
- Satu test = satu perilaku. Gunakan `dataset()` untuk variasi.
- Gunakan **factory state** yang bermakna (`->paid()`, `->verifiedInspector()`, `->withResult()`), bukan atribut mentah berulang.
- Helper bersama ada di `tests/Pest.php` dan `tests/Helpers`. Helper khusus domain ada di folder test domain.
- Uji **efek**, bukan implementasi: periksa status, ledger, event, respons. Jangan mengunci urutan pemanggilan internal.

## 5. Pola wajib untuk fitur tertentu

### Otorisasi

Setiap rute dan komponen diuji dengan **semua role**:

```php
it('AUTH-T05 forbids non-owners', function (string $role) {
    actingAs(userWithRole($role))->get(route('listings.edit', $listing))->assertForbidden();
})->with(['buyer', 'other seller', 'inspector']);
```

### Privasi data

Setiap respons publik diuji tidak memuat `lat`, `lng`, `address_full`, dan `chassis_number`. Ada helper `assertNoPrivateListingData($response)`.

### Uang dan ledger

Setiap pembayaran, refund, dan pencairan diuji dengan **invarian**:

- jumlah entri ledger pembayaran = nilai pembayaran,
- `platform + recipient = amount` (termasuk nilai ganjil yang membutuhkan pembulatan),
- refund menetralkan entri sebelumnya,
- tarif yang tersalin pada job tidak berubah saat pengaturan berubah.

### Konkurensi

- Tes pembaruan berkondisi: panggilan kedua pada status yang sudah berubah gagal dan tidak mengubah data.
- Tes batasan database: memasukkan job aktif kedua pada listing yang sama langsung lewat query harus ditolak oleh indeks unik (`QueryException`).
- Tes idempotensi: dua permintaan dengan `idempotency_key` sama menghasilkan satu pembayaran.

### State machine

Untuk setiap Enum status: dataset semua pasangan (dari, ke) yang sah harus diterima, dan semua pasangan lain ditolak.

### Tugas terjadwal

Bekukan waktu pada batas (`29 menit` vs `31 menit`, `6 hari` vs `7 hari`), jalankan command, periksa hasilnya.

### Livewire

`Livewire::test(Component::class)` untuk: render awal, validasi (`assertHasErrors`), aksi sukses, aksi ditolak (`assertForbidden`), dan *empty/loading/error state* yang ada di [UI_GUIDELINES](UI_GUIDELINES.md).

## 6. Arch test

`tests/Arch/DomainBoundariesTest.php` menjaga [ARCHITECTURE.md](ARCHITECTURE.md):

```php
arch('domains do not use other domains internals')
    ->expect('App\Domains\Listing')
    ->not->toUse([
        'App\Domains\Chat\Livewire', 'App\Domains\Chat\Actions',
        // diulang untuk setiap pasangan oleh helper di tests/Arch
    ]);

arch('support has no domain dependency')->expect('App\Support')->not->toUse('App\Domains');
arch('no debug calls')->expect(['dd', 'dump', 'ray', 'var_dump'])->not->toBeUsed();
arch('actions are final')->expect('App\Domains\*\Actions')->toBeFinal();
arch('models are not used for writes outside owner')->todo(); // diisi saat ada pola yang bisa dicek otomatis
```

Aturan arch baru ditambahkan bila ada pelanggaran yang berulang.

## 7. Journey test (lintas domain)

Folder `tests/Feature/Journeys` berisi sedikit test panjang yang memverifikasi alur utama PRD, misalnya:

1. `J1` penjual membuat listing, admin menyetujui, pembeli mengajukan inspeksi, bayar, inspektor mengambil dan mengirim hasil, pemohon menerima, lencana muncul.
2. `J2` pembeli lain membuka hasil berbayar, ledger inspektor dan platform benar.
3. `J3` transaksi jual beli sampai Selesai dan rating (P1).

Journey test hanya memeriksa sambungan antar-domain. Detail tetap diuji di domain masing-masing.

## 8. Cakupan

- Cakupan kode (pcov di CI): minimal **80%** untuk `app/Domains` dan **100%** untuk `Support\Money`, ledger, dan semua Enum status.
- Penegakan dua lapis di CI: `pest --coverage --min=80` untuk global, plus job khusus yang menjalankan spek jalur uang kritis (`MoneyTest`, seluruh `Payment/Tests`, dan `*StatusTest` job/transaksi/listing/payment) sehingga pelanggaran di subset kritis menggagalkan CI walau global di atas 80%.
- Cakupan adalah alat bantu, bukan tujuan. Test tanpa asersi bermakna ditolak saat review.

## 9. Perintah

```bash
composer test                               # semua test
composer test -- --filter=JOB-T03           # satu test berdasarkan ID
composer test -- --testsuite=Domain:Listing # satu domain
composer test -- --coverage --min=80
```

## 10. Daftar periksa test sebelum membuka PR

- [ ] Setiap Action, Policy, Enum, dan Rule baru punya unit test.
- [ ] Setiap rute, komponen Livewire, Listener, dan command baru punya feature test.
- [ ] Jalur sukses, otorisasi semua role, validasi gagal, dan edge case di flow doc teruji.
- [ ] Nama test memuat ID test case dari flow doc.
- [ ] Respons publik tidak membocorkan data persis.
- [ ] `composer check` lulus lokal.
