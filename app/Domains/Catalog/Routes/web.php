<?php

declare(strict_types=1);

use App\Domains\Catalog\Http\Controllers\CatalogController;
use App\Domains\Catalog\Http\Controllers\ShowListingController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/listings', CatalogController::class)
        ->name('catalog.index');

    Route::get('/listings/{listing}', ShowListingController::class)
        ->whereUuid('listing')
        ->name('catalog.show');
});
