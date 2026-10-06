<?php

declare(strict_types=1);

return [
    'title' => 'Dashboard',
    'welcome' => 'Selamat datang, :name',
    'coming_soon' => 'Segera hadir',
    'unknown_role' => 'Peran pengguna tidak dikenal',

    'buyer' => [
        'title' => 'Pembeli',
        'running_inspections' => 'Inspeksi Berjalan',
        'accessible_results' => 'Hasil yang Tersedia',
        'active_transactions' => 'Transaksi Aktif',
    ],

    'seller' => [
        'title' => 'Penjual',
        'active_listings' => 'Barang Aktif',
        'inspection_status' => 'Status Inspeksi',
        'buy_offers' => 'Pengajuan Pembelian',
    ],

    'empty_state' => [
        'title' => 'Mulai Jual atau Beli Kendaraan',
        'message' => 'Tidak ada aktivitas terkini. Cari kendaraan yang Anda inginkan atau tambahkan barang untuk dijual.',
        'search_vehicles' => 'Cari Kendaraan',
        'add_listing' => 'Tambah Barang',
    ],

    'inspector' => [
        'title' => 'Inspektor',
        'available_jobs' => 'Job Tersedia',
        'active_jobs' => 'Job Aktif',
        'net_earnings' => 'Pendapatan Bersih',
        'total_inspections' => 'Total Inspeksi',
        'rating' => 'Rating',
        'view_available_jobs' => 'Lihat Job Tersedia',
        'view_earnings' => 'Lihat Pendapatan',
        'empty_state' => [
            'title' => 'Belum Ada Job',
            'message' => 'Belum ada job yang tersedia untuk Anda saat ini. Cek kembali nanti atau hubungi admin.',
            'browse_jobs' => 'Lihat Job Tersedia',
        ],
    ],

    'admin' => [
        'title' => 'Admin',
        'total_users' => 'Total Pengguna',
        'total_inspectors' => 'Total Inspektor',
        'platform_earnings' => 'Pendapatan Platform',
        'jobs_by_status' => 'Job Berdasarkan Status',
        'listing_queue' => 'Antrean Listing',
        'pending_review' => 'Menunggu Validasi',
        'inspector_queue' => 'Antrean Inspektor',
        'pending_verification' => 'Menunggu Verifikasi',
        'items_needing_action' => 'Item Perlu Tindakan',
        'attention_required' => 'Perlu Perhatian',
        'all_clear' => 'Semua Lancar',
        'view_queue' => 'Lihat Antrean',
        'manage_settings' => 'Kelola Pengaturan',
        'manage_users' => 'Kelola Pengguna',
    ],
];
