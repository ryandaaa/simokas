<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // CAT-3: `/` is the catalog home backed by the listings table,
        // so this smoke test must migrate a fresh database itself instead
        // of relying on tables left behind by other tests.
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
