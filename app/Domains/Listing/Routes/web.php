<?php

declare(strict_types=1);

use App\Domains\Listing\Http\Controllers\ViewListingPhotoController;
use App\Domains\Listing\Livewire\CreateListing;
use App\Domains\Listing\Livewire\EditListing;
use App\Domains\Listing\Livewire\SellerListings;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    // Foto listing: disajikan dari storage private dengan otorisasi
    Route::get('/listings/{listing}/photos/{photo}', ViewListingPhotoController::class)
        ->name('listings.photos.show');

    // Rute autentikasi penjual
    Route::middleware(['auth', 'not_blocked'])->group(function () {
        Route::get('/listings/create', CreateListing::class)
            ->middleware('role:user')
            ->name('listings.create');

        Route::get('/listings/mine', SellerListings::class)
            ->middleware('role:user')
            ->name('listings.mine');

        Route::get('/listings/{listing}/edit', EditListing::class)
            ->name('listings.edit');
    });
});
