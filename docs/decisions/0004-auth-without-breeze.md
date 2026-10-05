# 0004. Implementasi Autentikasi Nativ Tanpa Laravel Breeze

- **Status:** Diterima (5 Oktober 2026)
- **Pembuat:** Tim Pengembang SIMOKAS
- **Domain:** Identity (`app/Domains/Identity`)

## Konteks

Kartu kanban `[AUTH-1]` (Issue #5) mencakup fitur autentikasi: registrasi akun, login dengan pesan generik, logout, lupa dan reset password via tautan email, rate limiting, penanganan pengguna diblokir, serta seeder akun admin.

Ringkasan awal issue menyebutkan penggunaan Laravel Breeze. Namun, proyek SIMOKAS mengadopsi arsitektur Domain-Driven Modular (`app/Domains/<Domain>`) dengan batas domain yang ketat, Action per use case, pemisahan lapisan (Action, Policy, Enum, DTO, Livewire), dan kepatuhan penuh terhadap UI Kit bersama (`<x-ui.*>`, `<x-layouts.app>`).

Paket Laravel Breeze memasang berkas controller, view, dan rute di luar batas domain (pada `app/Http/Controllers/Auth`, `resources/views/auth`, `routes/auth.php`), menggunakan Tailwind bawaan Breeze yang bertentangan dengan guardrail UI Kit kami, serta mempersulit pemisahan domain.

## Keputusan

1. **Tidak memasang Laravel Breeze** maupun paket scaffolding autentikasi pihak ketiga lainnya.
2. Membangun seluruh alur autentikasi secara mandiri (*native*) memanfaatkan pustaka dan komponen inti Laravel:
   - `Illuminate\Support\Facades\Auth` untuk otentikasi sesi berbasis guard `web`.
   - `Illuminate\Auth\Passwords\PasswordBroker` dan token broker bawaan untuk reset kata sandi.
   - `Illuminate\Support\Facades\RateLimiter` untuk pembatasan laju (*rate limiting*) login dan permintaan reset kata sandi.
   - `Illuminate\Notifications\Notification` untuk pengiriman email tautan reset kata sandi berbahasa Indonesia (`lang/id`).
3. Mengatur struktur autentikasi sepenuhnya di dalam domain Identity:
   - **Model:** `App\Domains\Identity\Models\User` (memakai UUID primary key).
   - **Enums:** `UserRole` (`user`, `inspector`, `admin`) dan `UserStatus` (`active`, `pending`, `blocked`).
   - **Actions:** `RegisterUser`, `AuthenticateUser`, `SendPasswordResetLink`, `ResetPassword`.
   - **Livewire Components:** `Register`, `Login`, `ForgotPassword`, `ResetPassword`, `Dashboard`, `Blocked`.
   - **Rules:** `IndonesianPhoneNumber` untuk validasi nomor HP Indonesia.
   - **Policies:** `UserPolicy` untuk otorisasi akses pengguna.
   - **Middleware:** `EnsureUserNotBlocked` untuk memblokir akses pengguna yang berstatus `blocked`.
   - **Seeder:** `AdminUserSeeder` dipanggil oleh `ReferenceSeeder` dengan kredensial dari environment variable (`ADMIN_EMAIL`, `ADMIN_PASSWORD`).
   - **Views:** Menggunakan komponen UI Kit bersama (`<x-ui.*>`) dan layout `<x-layouts.app>`, tanpa kode warna heksadesimal atau gaya inline.

## Konsekuensi

- **Positif:**
  - Kode bersih, terisolasi 100% di dalam domain Identity sesuai panduan arsitektur (`ARCHITECTURE.md`).
  - Tidak ada dependensi tambahan yang tidak perlu di `composer.json`.
  - Sepenuhnya mematuhi guardrail UI Kit dan desain token Tailwind v4.
  - Memudahkan pengujian unit pada Action dan pengujian feature pada komponen Livewire menggunakan Pest.
- **Tantangan:**
  - Menulis sendiri komponen Livewire dan Action untuk setiap use case, namun memberikan kendali penuh terhadap alur bisnis dan pesan berbahasa Indonesia.
