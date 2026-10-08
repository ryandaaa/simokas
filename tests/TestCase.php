<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Vite;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Paksa Vite memakai manifest build agar hasil test deterministik
        // walau `npm run dev` sedang jalan (public/hot diabaikan).
        Vite::useHotFile('hot.testing-missing');
    }
}
