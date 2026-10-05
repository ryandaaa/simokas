<?php

declare(strict_types=1);

it('renders component gallery at /_ui when environment is local or testing', function () {
    $response = $this->get('/_ui');

    $response->assertStatus(200);
    $response->assertSee('Galeri Komponen UI SIMOKAS');
    $response->assertSee('1. Token Desain');
    $response->assertSee('2. Komponen Ikon');
    $response->assertSee('3. Tombol');
    $response->assertSee('4. Komponen Formulir');
    $response->assertSee('5. Status Chip');
    $response->assertSee('6. Uang');
    $response->assertSee('7. Stepper');
    $response->assertSee('8. Kartu Listing');
    $response->assertSee('9. Peringatan');
    $response->assertSee('10. Kamera');
    $response->assertSee('11. Pagination');
});

it('returns 404 for /_ui when environment is production', function () {
    app()->detectEnvironment(fn () => 'production');

    $response = $this->get('/_ui');

    $response->assertStatus(404);
});
