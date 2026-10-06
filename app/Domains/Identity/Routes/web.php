<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Http\Controllers\LogoutController;
use App\Domains\Identity\Http\Controllers\PrivacyPolicyController;
use App\Domains\Identity\Http\Controllers\TermsController;
use App\Domains\Identity\Livewire\AccountSettings;
use App\Domains\Identity\Livewire\AdminDashboard;
use App\Domains\Identity\Livewire\Blocked;
use App\Domains\Identity\Livewire\ForgotPassword;
use App\Domains\Identity\Livewire\InspectorDashboard;
use App\Domains\Identity\Livewire\Login;
use App\Domains\Identity\Livewire\Register;
use App\Domains\Identity\Livewire\ResetPassword;
use App\Domains\Identity\Livewire\UserDashboard;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/privacy', PrivacyPolicyController::class)->name('legal.privacy');
    Route::get('/terms', TermsController::class)->name('legal.terms');

    Route::middleware('guest')->group(function () {
        Route::get('/register', Register::class)->name('identity.register');
        Route::get('/login', Login::class)->name('login');
        Route::redirect('/identity/login', '/login')->name('identity.login');
        Route::get('/forgot-password', ForgotPassword::class)->name('identity.forgot-password');
        Route::get('/reset-password/{token}', ResetPassword::class)->name('identity.reset-password');
    });

    Route::middleware(['auth', 'not_blocked'])->group(function () {
        Route::get('/dashboard', function () {
            /** @var User|null $user */
            $user = Auth::user();

            return match ($user?->role) {
                UserRole::Admin => redirect()->route('dashboard.admin'),
                UserRole::Inspector => redirect()->route('dashboard.inspector'),
                default => redirect()->route('dashboard.user'),
            };
        })->name('dashboard');

        Route::redirect('/identity/dashboard', '/dashboard')->name('identity.dashboard');

        Route::get('/dashboard/user', UserDashboard::class)
            ->middleware('role:user')
            ->name('dashboard.user');

        Route::get('/dashboard/inspector', InspectorDashboard::class)
            ->middleware('role:inspector')
            ->name('dashboard.inspector');

        Route::get('/dashboard/admin', AdminDashboard::class)
            ->middleware('role:admin')
            ->name('dashboard.admin');

        Route::get('/account', AccountSettings::class)
            ->middleware('role:user,inspector,admin')
            ->name('identity.account');
    });

    Route::post('/logout', LogoutController::class)->middleware('auth')->name('identity.logout');
    Route::get('/blocked', Blocked::class)->name('identity.blocked');
});
