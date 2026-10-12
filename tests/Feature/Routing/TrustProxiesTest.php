<?php

declare(strict_types=1);

// Kepercayaan proxy + cookie aman (Batch 2 audit prod): di belakang proxy
// Hostinger, skema HTTPS hanya terdeteksi bila proxy dipercaya. Tanpa ini,
// signed URL (foto/dokumen) dan cookie secure salah memakai http.

it('trusts proxy forwarded proto so generated URLs use https', function () {
    $this->call('GET', '/', [], [], [], ['HTTP_X_FORWARDED_PROTO' => 'https']);

    expect(url('/'))->toStartWith('https://');
});

it('defaults session cookie to insecure outside production', function () {
    // APP_ENV=testing (phpunit.xml): default harus false agar login lokal
    // dan test tetap jalan; di production default-nya true.
    expect(config('session.secure'))->toBeFalse();
});
