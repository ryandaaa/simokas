# Fitur: Pola Otorisasi Lintas Domain (AUTH-2)

| | |
| --- | --- |
| **Domain** | Identity |
| **Prioritas** | P0 |
| **Status** | Selesai |
| **Referensi PRD** | Bagian 10 (Hak Akses) |
| **Flow doc** | `docs/flows/auth.md` |

## 1. Ringkasan

Menyediakan standar otorisasi berlapis (*defense-in-depth*) yang wajib digunakan oleh seluruh domain di SIMOKAS:
1. **Lapisan Rute (Middleware Role):** Membatasi akses rute berdasarkan peran (`user`, `inspector`, `admin`).
2. **Lapisan Logika Bisnis (Policy & Gate):** Memeriksa kepemilikan data, status akun, dan aturan bisnis spesifik sebelum menjalankan aksi di Action, Livewire, atau Controller.

## 2. Aturan Batas Domain (Rule A3)

Untuk menjaga modularitas dan arsitektur domain:
* **Hanya gunakan alias string middleware:** Domain lain mendaftarkan middleware dengan string `'role:<role>'` (mis. `'role:admin'`, `'role:inspector,user'`).
* **Dilarang mengimpor class internal Identity:** Jangan pernah mengimpor class `App\Domains\Identity\Http\Middleware\EnsureUserHasRole` di domain lain.
* **Gunakan Enum / Helper Publik:** Status peran dapat dicek menggunakan Enum publik `App\Domains\Identity\Enums\UserRole` atau method helper pada model User (`$user->isAdmin()`, `$user->isInspector()`, `$user->isUser()`).

## 3. Middleware Role

Didaftarkan secara otomatis oleh `IdentityServiceProvider` dengan alias `role`.

### 3.1 Sintaks Penggunaan Rute

```php
use Illuminate\Support\Facades\Route;

// Satu role tertentu
Route::middleware(['auth', 'not_blocked', 'role:admin'])->group(function () {
    Route::get('/admin/queue', AdminQueueController::class);
});

// Multi role (dipisahkan koma)
Route::middleware(['auth', 'not_blocked', 'role:inspector,user'])->group(function () {
    Route::get('/orders', OrderHistoryController::class);
});
```

### 3.2 Perilaku Penolakan (403)
* **Permintaan Web (HTML):** Ditolak dengan kode status HTTP `403` dan menampilkan halaman error ramah `resources/views/errors/403.blade.php` (F2 Polos: "Akses Ditolak").
* **Permintaan API / JSON (`expectsJson` atau `/api/*`):** Ditolak dengan `DomainException` berkode `NOT_ALLOWED` dan HTTP `403`, menghasilkan format standar:
  ```json
  {
    "data": null,
    "error": {
      "code": "NOT_ALLOWED",
      "message": "Anda tidak memiliki hak akses untuk halaman atau aksi ini."
    }
  }
  ```
* **Pengguna Belum Login:** Web diarahkan ke halaman login (`route('login')`), JSON menghasilkan `401` (`UNAUTHENTICATED`).
* **Pengguna Berstatus `blocked`:** Sesi langsung dibatalkan (`Auth::logout()`), web diarahkan ke `/blocked`, JSON menghasilkan `403` (`NOT_ALLOWED`).

## 4. Pola Policy dan Gate per Domain

Setiap domain yang memiliki entitas data wajib mendefinisikan Policy sendiri (misalnya `ListingPolicy` di domain Listing, `InspectionPolicy` di domain Inspection).

### 4.1 Pendaftaran Policy di ServiceProvider Domain

```php
// app/Domains/Listing/Providers/ListingServiceProvider.php
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Policies\ListingPolicy;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::policy(Listing::class, ListingPolicy::class);
}
```

### 4.2 Contoh Implementasi Policy Domain

```php
// app/Domains/Listing/Policies/ListingPolicy.php
namespace App\Domains\Listing\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;

final class ListingPolicy
{
    public function update(User $user, Listing $listing): bool
    {
        // Admin atau pemilik listing yang bersangkutan
        return $user->isAdmin() || $user->id === $listing->user_id;
    }

    public function create(User $user): bool
    {
        // Hanya pengguna (pembeli/penjual) dan admin yang boleh membuat listing
        return $user->canSell();
    }
}
```

### 4.3 Pemanggilan di Action / Controller

```php
// Di dalam Controller atau Action
$this->authorize('update', $listing);

// Atau menggunakan fasad Gate secara eksplisit
use Illuminate\Support\Facades\Gate;
Gate::authorize('update', $listing);
```

### 4.4 Pemanggilan di Blade UI

Gunakan direktif `@can` bawaan Blade untuk menyembunyikan atau menampilkan elemen antarmuka:

```blade
@can('update', $listing)
    <x-ui.button :href="route('listings.edit', $listing)" variant="secondary">
        Edit Kendaraan
    </x-ui.button>
@endcan
```

## 5. Ringkasan Matriks Akses Per Role (PRD 10)

| Fitur / Aksi | Publik | Pembeli/Penjual (`user`) | Inspektor (`inspector`) | Admin (`admin`) |
| --- | :---: | :---: | :---: | :---: |
| Halaman Dashboard | - | `dashboard.user` | `dashboard.inspector` | `dashboard.admin` |
| Mengajukan Inspeksi | - | Ya | Tidak (D-06) | Tidak |
| Menjual / Membeli | - | Ya | Tidak (D-06) | Ya (Admin) |
| Mengambil Job Inspeksi | - | Tidak | Ya (Terverifikasi) | Tidak |
| Verifikasi Akun & Listing | - | Tidak | Tidak | Ya |
| Manajemen Pengguna | - | Tidak | Tidak | Ya |
