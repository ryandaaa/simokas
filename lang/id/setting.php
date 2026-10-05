<?php

declare(strict_types=1);

return [
    'keys' => [
        'inspection_fee_car' => 'Tarif Inspeksi Mobil',
        'inspection_fee_motorcycle' => 'Tarif Inspeksi Motor',
        'platform_fee_pct' => 'Komisi Platform',
        'sale_commission_pct' => 'Komisi Transaksi Penjualan',
        'auto_accept_days' => 'Batas Konfirmasi Hasil Otomatis',
        'paid_job_timeout_days' => 'Batas Waktu Pengambilan Job',
    ],
    'hints' => [
        'inspection_fee_car' => 'Biaya inspeksi dan biaya akses laporan untuk mobil (Rp, bulat > 0).',
        'inspection_fee_motorcycle' => 'Biaya inspeksi dan biaya akses laporan untuk motor (Rp, bulat > 0).',
        'platform_fee_pct' => 'Persentase potongan platform dari tarif inspeksi dan akses (0–100%).',
        'sale_commission_pct' => 'Persentase komisi transaksi untuk inspektor (0–100%).',
        'auto_accept_days' => 'Waktu job hasil inspeksi otomatis selesai bila pemohon tidak menekan tombol (≥ 1 hari).',
        'paid_job_timeout_days' => 'Batas waktu job berbayar dibatalkan otomatis bila tidak ada inspektor yang mengambil (≥ 1 hari).',
    ],
    'units' => [
        'rupiah' => 'Rp',
        'percent' => '%',
        'days' => 'hari',
    ],
    'validation' => [
        'fee_positive' => ':attribute harus berupa bilangan bulat lebih dari 0.',
        'pct_range' => ':attribute harus berupa persentase antara 0 dan 100.',
        'days_min' => ':attribute harus berupa bilangan bulat minimal 1 hari.',
        'missing_key' => 'Pengaturan :key wajib diisi.',
    ],
];
