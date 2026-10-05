<?php

use Illuminate\Support\Facades\Route;

// Note (CAT-3, shared): the default `/` welcome route moved to the
// Catalog domain (`home` route served by HomeController).

Route::get('/_ui', function () {
    abort_unless(app()->environment(['local', 'testing']), 404);

    return view('ui.gallery');
})->name('ui.gallery');
