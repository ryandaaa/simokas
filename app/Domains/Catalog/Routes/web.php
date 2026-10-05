<?php

declare(strict_types=1);

use App\Domains\Catalog\Http\Controllers\CatalogController;
use App\Domains\Catalog\Http\Controllers\HelpController;
use App\Domains\Catalog\Http\Controllers\HomeController;
use App\Domains\Catalog\Http\Controllers\HowItWorksController;
use App\Domains\Catalog\Http\Controllers\ShowListingController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/', HomeController::class)
        ->name('home');

    Route::get('/cara-kerja', HowItWorksController::class)
        ->name('catalog.how-it-works');

    Route::get('/bantuan', HelpController::class)
        ->name('catalog.help');

    Route::get('/listings', CatalogController::class)
        ->name('catalog.index');

    Route::get('/listings/{listing}', ShowListingController::class)
        ->whereUuid('listing')
        ->name('catalog.show');
});
