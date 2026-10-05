<?php

declare(strict_types=1);

namespace App\Domains\Result\Database\seeders;

use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Result\Models\InspectionTemplate;
use App\Domains\Result\Models\TemplateItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InspectionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedCarTemplate();
            $this->seedMotorcycleTemplate();
        });
    }

    private function seedCarTemplate(): void
    {
        /** @var InspectionTemplate $template */
        $template = InspectionTemplate::query()->firstOrCreate(
            [
                'vehicle_type' => VehicleType::Car->value,
                'version' => 1,
            ],
            [
                'name' => 'Template Inspeksi Mobil v1',
                'is_active' => true,
            ]
        );

        $items = [
            // Dokumen
            ['category' => 'Dokumen', 'label' => 'STNK', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 1],
            ['category' => 'Dokumen', 'label' => 'BPKB', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 2],
            ['category' => 'Dokumen', 'label' => 'Pajak', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 3],
            ['category' => 'Dokumen', 'label' => 'Kecocokan nomor rangka dan mesin', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => true, 'sort_order' => 4],

            // Eksterior
            ['category' => 'Eksterior', 'label' => 'Bodi', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 5],
            ['category' => 'Eksterior', 'label' => 'Cat', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 6],
            ['category' => 'Eksterior', 'label' => 'Kaca', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 7],
            ['category' => 'Eksterior', 'label' => 'Lampu', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 8],
            ['category' => 'Eksterior', 'label' => 'Ban', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 9],

            // Interior
            ['category' => 'Interior', 'label' => 'Jok', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 10],
            ['category' => 'Interior', 'label' => 'Dashboard', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 11],
            ['category' => 'Interior', 'label' => 'AC', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 12],
            ['category' => 'Interior', 'label' => 'Speedometer', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 13],

            // Mesin
            ['category' => 'Mesin', 'label' => 'Kondisi mesin', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 14],
            ['category' => 'Mesin', 'label' => 'Kebocoran oli', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 15],
            ['category' => 'Mesin', 'label' => 'Suara mesin', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 16],

            // Kaki-kaki
            ['category' => 'Kaki-kaki', 'label' => 'Suspensi', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 17],
            ['category' => 'Kaki-kaki', 'label' => 'Rem', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 18],
            ['category' => 'Kaki-kaki', 'label' => 'Rangka', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => true, 'sort_order' => 19],
            ['category' => 'Kaki-kaki', 'label' => 'Roda', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 20],

            // Kelistrikan
            ['category' => 'Kelistrikan', 'label' => 'Aki', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 21],
            ['category' => 'Kelistrikan', 'label' => 'Lampu utama & sein', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 22],
            ['category' => 'Kelistrikan', 'label' => 'Klakson', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 23],
            ['category' => 'Kelistrikan', 'label' => 'Kelistrikan umum', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 24],

            // Test drive
            ['category' => 'Test drive', 'label' => 'Perpindahan gigi', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 25],
            ['category' => 'Test drive', 'label' => 'Kemudi', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 26],
            ['category' => 'Test drive', 'label' => 'Rem saat berjalan', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 27],

            // Riwayat tabrak / banjir
            ['category' => 'Riwayat tabrak / banjir', 'label' => 'Indikasi bekas tabrak', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => true, 'sort_order' => 28],
            ['category' => 'Riwayat tabrak / banjir', 'label' => 'Indikasi bekas banjir', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => true, 'sort_order' => 29],
        ];

        foreach ($items as $itemData) {
            TemplateItem::query()->firstOrCreate(
                [
                    'template_id' => $template->id,
                    'label' => $itemData['label'],
                ],
                $itemData
            );
        }
    }

    private function seedMotorcycleTemplate(): void
    {
        /** @var InspectionTemplate $template */
        $template = InspectionTemplate::query()->firstOrCreate(
            [
                'vehicle_type' => VehicleType::Motorcycle->value,
                'version' => 1,
            ],
            [
                'name' => 'Template Inspeksi Motor v1',
                'is_active' => true,
            ]
        );

        $items = [
            // Dokumen
            ['category' => 'Dokumen', 'label' => 'STNK', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 1],
            ['category' => 'Dokumen', 'label' => 'BPKB', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 2],
            ['category' => 'Dokumen', 'label' => 'Pajak', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 3],
            ['category' => 'Dokumen', 'label' => 'Kecocokan nomor rangka dan mesin', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => true, 'sort_order' => 4],

            // Eksterior
            ['category' => 'Eksterior', 'label' => 'Bodi & fairing', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 5],
            ['category' => 'Eksterior', 'label' => 'Cat', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 6],
            ['category' => 'Eksterior', 'label' => 'Spion & lampu', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 7],
            ['category' => 'Eksterior', 'label' => 'Ban depan & belakang', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 8],

            // Jok dan panel
            ['category' => 'Jok dan panel', 'label' => 'Jok', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 9],
            ['category' => 'Jok dan panel', 'label' => 'Speedometer & panel instrumen', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 10],
            ['category' => 'Jok dan panel', 'label' => 'Kunci kontak / smartkey', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 11],

            // Mesin
            ['category' => 'Mesin', 'label' => 'Kondisi mesin', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 12],
            ['category' => 'Mesin', 'label' => 'Kebocoran oli', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 13],
            ['category' => 'Mesin', 'label' => 'Suara mesin & knalpot', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 14],

            // Kaki-kaki
            ['category' => 'Kaki-kaki', 'label' => 'Suspensi depan & belakang', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 15],
            ['category' => 'Kaki-kaki', 'label' => 'Rem depan & belakang', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 16],
            ['category' => 'Kaki-kaki', 'label' => 'Rangka & swingarm', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => true, 'sort_order' => 17],
            ['category' => 'Kaki-kaki', 'label' => 'Pelek & rantai/belt', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => false, 'sort_order' => 18],

            // Kelistrikan
            ['category' => 'Kelistrikan', 'label' => 'Aki', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 19],
            ['category' => 'Kelistrikan', 'label' => 'Lampu & sein', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 20],
            ['category' => 'Kelistrikan', 'label' => 'Klakson', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 21],
            ['category' => 'Kelistrikan', 'label' => 'Starter elektrik', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 22],

            // Test ride
            ['category' => 'Test ride', 'label' => 'Akselerasi & perpindahan gigi', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 23],
            ['category' => 'Test ride', 'label' => 'Kestabilan kemudi / stang', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 24],
            ['category' => 'Test ride', 'label' => 'Pengereman saat berjalan', 'weight' => 1, 'is_required' => true, 'requires_photo' => false, 'is_critical' => false, 'sort_order' => 25],

            // Riwayat tabrak / banjir
            ['category' => 'Riwayat tabrak / banjir', 'label' => 'Indikasi bekas tabrak', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => true, 'sort_order' => 26],
            ['category' => 'Riwayat tabrak / banjir', 'label' => 'Indikasi bekas banjir', 'weight' => 1, 'is_required' => true, 'requires_photo' => true, 'is_critical' => true, 'sort_order' => 27],
        ];

        foreach ($items as $itemData) {
            TemplateItem::query()->firstOrCreate(
                [
                    'template_id' => $template->id,
                    'label' => $itemData['label'],
                ],
                $itemData
            );
        }
    }
}
