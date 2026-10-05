<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/_ui', function () {
    abort_unless(app()->environment(['local', 'testing']), 404);

    return view('ui.gallery');
})->name('ui.gallery');
