<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ReferenceSeeder::class);

        $seedDemo = filter_var(env('SEED_DEMO', true), FILTER_VALIDATE_BOOLEAN);

        if (! app()->environment('production') && $seedDemo) {
            $this->call(DemoSeeder::class);
        }
    }
}
