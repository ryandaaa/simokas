<?php

declare(strict_types=1);

namespace App\Domains\Setting\Database\Seeders;

use App\Domains\Setting\Enums\SettingKey;
use App\Domains\Setting\Models\AppSetting;
use Illuminate\Database\Seeder;

final class SettingSeeder extends Seeder
{
    /**
     * Seed pengaturan default aplikasi secara idempoten.
     * Tidak akan menimpa pengaturan yang sudah ada atau pernah diubah admin.
     */
    public function run(): void
    {
        foreach (SettingKey::cases() as $key) {
            AppSetting::firstOrCreate(
                ['key' => $key->value],
                [
                    'value' => $key->defaultValue(),
                    'updated_by' => null,
                ]
            );
        }
    }
}
