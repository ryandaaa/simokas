<?php

declare(strict_types=1);

it('renders error views with friendly Indonesian messages and layout components', function () {
    // 403 Forbidden
    $view403 = $this->view('errors.403');
    $view403->assertSee('Galat 403');
    $view403->assertSee('Akses Ditolak');
    $view403->assertSee('tidak memiliki izin');

    // 404 Not Found
    $view404 = $this->view('errors.404');
    $view404->assertSee('Galat 404');
    $view404->assertSee('Halaman Tidak Ditemukan');
    $view404->assertSee('tidak dapat ditemukan');

    // 500 Server Error
    $view500 = $this->view('errors.500');
    $view500->assertSee('Galat 500');
    $view500->assertSee('Terjadi Kesalahan Server');

    // 419 Page Expired
    $view419 = $this->view('errors.419');
    $view419->assertSee('Galat 419');
    $view419->assertSee('Sesi Berakhir');

    // 429 Too Many Requests
    $view429 = $this->view('errors.429');
    $view429->assertSee('Galat 429');
    $view429->assertSee('Terlalu Banyak Permintaan');

    // 503 Service Unavailable
    $view503 = $this->view('errors.503');
    $view503->assertSee('Galat 503');
    $view503->assertSee('Layanan Sedang Dalam Pemeliharaan');
});
