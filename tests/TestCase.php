<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Paksa Vite memakai manifest build agar hasil test deterministik
        // walau `npm run dev` sedang jalan (public/hot diabaikan).
        \Illuminate\Support\Facades\Vite::useHotFile('hot.testing-missing');
    }
}
