<?php

declare(strict_types=1);

return [
    'title' => 'Pembayaran Tagihan',
    'simulation_label' => 'SIMULASI',
    'simulation_badge_help' => 'Ini adalah lingkungan simulasi untuk demo. Tidak ada uang sungguhan yang didebit.',

    'status' => [
        'pending' => 'Menunggu Pembayaran',
        'success' => 'Berhasil',
        'failed' => 'Gagal',
        'refunded' => 'Dikembalikan',
    ],

    'purpose' => [
        'inspection' => 'Biaya Inspeksi',
        'access' => 'Akses Hasil Laporan',
        'purchase' => 'Pembelian Kendaraan',
    ],

    'methods' => [
        'qris' => 'QRIS',
        'transfer' => 'Transfer Bank',
        'ewallet' => 'E-Wallet',
    ],

    'outcomes' => [
        'success' => 'Berhasil',
        'failed' => 'Gagal',
        'pending' => 'Tertunda',
    ],

    'fields' => [
        'payment_id' => 'ID Pembayaran',
        'amount' => 'Jumlah Tagihan',
        'purpose' => 'Tujuan',
        'status' => 'Status',
        'method' => 'Metode Pembayaran',
        'outcome' => 'Hasil Simulasi',
        'expires_at' => 'Batas Pembayaran',
        'paid_at' => 'Waktu Pembayaran',
        'idempotency_key' => 'Kunci Idempotensi',
    ],

    'actions' => [
        'pay' => 'Bayar Sekarang (Simulasi)',
        'retry' => 'Coba Bayar Lagi',
        'confirm_title' => 'Konfirmasi Pembayaran Simulasi',
        'confirm_description' => 'Apakah Anda yakin ingin melanjutkan pembayaran simulasi ini? Tindakan ini akan mengubah status tagihan dan memperbarui data terkait.',
        'confirm_button' => 'Ya, Lanjutkan Pembayaran',
        'processing' => 'Memproses...',
    ],

    'messages' => [
        'payment_successful' => 'Pembayaran simulasi berhasil diproses.',
        'payment_failed' => 'Pembayaran simulasi gagal. Anda dapat mencoba kembali.',
        'payment_pending' => 'Pembayaran simulasi berstatus tertunda.',
        'already_paid' => 'Tagihan ini telah berhasil dibayar.',
    ],

    'errors' => [
        'payment_expired' => 'Tagihan ini telah kedaluwarsa dan tidak dapat dibayar lagi.',
        'already_processed' => 'Pembayaran sudah diproses sebelumnya dan tidak dapat diubah lagi.',
        'invalid_transition' => 'Transisi status pembayaran dari :from ke :to tidak sah.',
        'free_outcome_not_allowed' => 'Pilihan hasil simulasi bebas tidak tersedia di lingkungan produksi.',
        'unauthorized' => 'Anda tidak memiliki hak untuk membayar atau melihat tagihan ini.',
        'cannot_expire_processed' => 'Tagihan yang sudah berhasil atau dikembalikan tidak dapat dikedaluwarsakan.',
    ],
];
