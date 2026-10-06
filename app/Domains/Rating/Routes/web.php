<?php

declare(strict_types=1);

use App\Domains\Rating\Livewire\AdminRatingList;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'not_blocked', 'role:admin'])->group(function () {
    Route::get('/admin/ratings', AdminRatingList::class)->name('admin.ratings.index');
});
