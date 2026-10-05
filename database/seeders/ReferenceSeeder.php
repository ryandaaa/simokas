<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Identity\Database\Seeders\AdminUserSeeder;
use App\Domains\Result\Database\seeders\InspectionTemplateSeeder;
use App\Domains\Setting\Database\Seeders\SettingSeeder;
use Illuminate\Database\Seeder;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SettingSeeder::class,
            InspectionTemplateSeeder::class,
        ]);
    }
}
