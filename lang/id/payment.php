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
        'idempotency_key_conflict' => 'Kunci idempotensi sudah dipakai untuk tagihan lain. Gunakan kunci baru yang unik untuk setiap tagihan.',
        'already_posted' => 'Bagi hasil pembayaran ini sudah dicatat di ledger dan tidak dapat diposting ulang.',
        'refund_not_allowed' => 'Refund hanya dapat dilakukan untuk pembayaran berstatus success (status saat ini: :status).',
        'refund_already_posted' => 'Pembayaran ini sudah diposting ke ledger sehingga tidak dapat di-refund.',
        'posting_not_success' => 'Bagi hasil hanya dapat diposting untuk pembayaran berstatus success (status saat ini: :status).',
        'posting_purpose_mismatch' => 'Jenis entri :type tidak sesuai dengan tujuan pembayaran :purpose.',
        'posting_invalid_inspector' => 'Penerima bagi hasil harus pengguna dengan peran inspektor yang valid.',
        'posting_invalid_percentage' => 'Persentase komisi platform harus bilangan bulat 0 sampai 100 (diberikan: :pct).',
        'posting_snapshot_required' => 'Posting fee inspeksi wajib memakai persentase komisi yang disalin pada job, bukan pengaturan saat ini.',
        'posting_invalid_commission' => 'Komisi :which tidak boleh negatif (diberikan: :amount).',
        'posting_inspector_exceeds_commission' => 'Komisi inspektor (:inspector) tidak boleh melebihi total komisi (:total).',
        'posting_commission_exceeds_payment' => 'Total komisi (:total) tidak boleh melebihi nominal pembayaran (:amount).',
        'ledger_entry_immutable' => 'Entri ledger bersifat append-only dan tidak dapat diubah atau dihapus.',
    ],

    'ledger' => [
        'types' => [
            'inspection_fee' => 'Fee Inspeksi',
            'access_fee' => 'Fee Akses Hasil',
            'sale_commission' => 'Komisi Transaksi',
            'platform_fee' => 'Komisi Platform',
            'refund' => 'Refund',
        ],
        'notes' => [
            'platform_fee' => 'Komisi platform :pct% dari pembayaran.',
            'payout' => 'Pembayaran :type kepada inspektor (komisi platform :pct%).',
            'purchase_platform_fee' => 'Komisi platform dari komisi penjualan.',
            'purchase_payout' => 'Komisi transaksi kepada inspektor.',
            'refund' => 'Pengembalian dana kepada pembayar.',
        ],
    ],

    'earnings' => [
        'title' => 'Pendapatan Saya',
        'subtitle' => 'Jumlah entri kredit ledger Anda. Simulasi tanpa penarikan nyata.',
        'balance' => 'Total Saldo',
        'balance_hint' => 'Saldo sama dengan jumlah seluruh entri kredit Anda di ledger.',
        'breakdown' => 'Rincian per Sumber',
        'entries_title' => 'Riwayat Pendapatan',
        'entries_count' => '1 entri|:count entri',
        'empty_title' => 'Belum ada pendapatan',
        'empty_description' => 'Pendapatan dari fee inspeksi dan akses hasil akan tercatat di sini setelah job selesai atau akses dibayar.',
        'load_failed_title' => 'Gagal memuat pendapatan',
        'load_failed' => 'Terjadi galat saat memuat data pendapatan. Silakan coba lagi.',
        'retry' => 'Coba lagi',
    ],
];
