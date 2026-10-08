<?php

declare(strict_types=1);

return [
    'unknown_user' => 'Pengguna',

    'types' => [
        'job_available' => 'Job baru',
        'payment_succeeded' => 'Pembayaran berhasil',
        'inspector_approved' => 'Verifikasi inspektor disetujui',
        'inspector_rejected' => 'Verifikasi inspektor ditolak',
        'listing_submitted' => 'Listing menunggu validasi',
        'listing_approved' => 'Listing disetujui',
        'listing_rejected' => 'Listing ditolak',
        'job_taken' => 'Job diambil',
        'job_scheduled' => 'Jadwal ditetapkan',
        'result_submitted' => 'Hasil dikirim',
        'job_cancelled' => 'Job dibatalkan',
        'job_completed' => 'Inspeksi selesai',
        'fee_released' => 'Fee cair',
        'message_received' => 'Pesan baru',
        'inspector_submitted' => 'Inspektor baru menunggu',
        'transaction_offered' => 'Pengajuan pembelian',
        'transaction_accepted' => 'Pengajuan disetujui',
        'transaction_rejected' => 'Pengajuan ditolak',
        'transaction_paid' => 'Pembayaran ditahan',
        'transaction_completed' => 'Transaksi selesai',
        'transaction_cancelled' => 'Transaksi dibatalkan',
        'transaction_expired' => 'Transaksi kedaluwarsa',
    ],

    'index' => [
        'title' => 'Notifikasi',
        'heading' => 'Notifikasi',
        'mark_all_read' => 'Tandai semua dibaca',
        'marked_all_read' => 'Semua notifikasi ditandai dibaca.',
        'empty_title' => 'Belum ada notifikasi',
        'empty_description' => 'Notifikasi tentang job, listing, pembayaran, dan pesan akan muncul di sini.',
        'open' => 'Buka',
    ],

    'bell' => [
        'label' => 'Notifikasi',
        'unread' => ':count belum dibaca',
    ],

    'job_available' => [
        'title' => 'Job inspeksi baru',
        'body' => 'Ada job baru untuk ":judul" yang sesuai keahlianmu.',
    ],

    'payment_success' => [
        'title' => 'Pembayaran berhasil',
        'body' => 'Pembayaran sebesar :nominal berhasil.',
    ],

    'job_taken' => [
        'title' => 'Job inspeksi diambil',
        'body_seller' => ':nama mengambil job inspeksi untuk ":judul".',
        'body_requester' => ':nama mengambil job inspeksimu.',
    ],

    'job_scheduled' => [
        'title' => 'Jadwal inspeksi ditetapkan',
        'body' => 'Inspeksi dijadwalkan pada :tanggal.',
    ],

    'job_cancelled' => [
        'title' => 'Job inspeksi dibatalkan',
        'body' => 'Job inspeksimu dibatalkan otomatis karena tidak diambil inspektor. Dana dikembalikan penuh.',
    ],

    'job_completed' => [
        'title' => 'Inspeksi selesai',
        'body' => 'Hasil inspeksi sudah final. Pembayaran untuk pembelian sudah dapat dilanjutkan.',
    ],

    'fee_released' => [
        'title' => 'Fee inspeksi cair',
        'body' => 'Fee sebesar :nominal telah dicairkan ke saldomu.',
    ],

    'result_submitted' => [
        'title' => 'Hasil inspeksi dikirim',
        'body' => 'Inspektor telah mengirim hasil inspeksi. Silakan periksa dan konfirmasi.',
    ],

    'listing_submitted' => [
        'title' => 'Listing menunggu validasi',
        'body' => 'Ada listing baru yang menunggu validasi admin.',
    ],

    'listing_approved' => [
        'title' => 'Listing disetujui',
        'body' => 'Listingmu sudah disetujui dan tampil di katalog.',
    ],

    'listing_rejected' => [
        'title' => 'Listing ditolak',
        'body' => 'Listingmu ditolak dengan alasan: :alasan',
    ],

    'inspector_approved' => [
        'title' => 'Verifikasi disetujui',
        'body' => 'Selamat! Verifikasi akun inspektormu disetujui. Kamu sudah bisa mengambil job.',
    ],

    'inspector_rejected' => [
        'title' => 'Verifikasi ditolak',
        'body' => 'Verifikasi akun inspektormu ditolak. Periksa kembali dokumenmu di pengaturan akun.',
    ],

    'chat' => [
        'title' => 'Pesan baru',
        'body_single' => ':nama mengirim pesan baru.',
        'body_grouped' => ':count pesan baru dari :nama.',
    ],

    'inspector_submitted' => [
        'title' => 'Inspektor baru menunggu verifikasi',
        'body' => ':nama mengajukan verifikasi sebagai inspektor.',
    ],

    'transaction_offered' => [
        'title' => 'Pengajuan pembelian baru',
        'body' => ':nama mengajukan pembelian untuk ":judul".',
    ],

    'transaction_accepted' => [
        'title' => 'Pengajuan pembelian disetujui',
        'body' => 'Penjual menyetujui pengajuanmu untuk ":judul". Silakan lanjutkan ke pembayaran.',
    ],

    'transaction_rejected' => [
        'title' => 'Pengajuan pembelian ditolak',
        'body' => 'Penjual menolak pengajuanmu untuk ":judul".',
    ],

    'transaction_paid' => [
        'title' => 'Pembeli sudah membayar',
        'body' => 'Pembayaran untuk ":judul" sudah masuk dan dananya ditahan platform.',
    ],

    'transaction_completed' => [
        'title' => 'Transaksi selesai',
        'body_seller' => 'Serah terima ":judul" selesai. Dana diteruskan setelah komisi.',
        'body_buyer' => 'Serah terima ":judul" selesai. Silakan beri rating untuk inspektor.',
    ],

    'transaction_cancelled' => [
        'title' => 'Transaksi dibatalkan',
        'body' => 'Transaksi untuk ":judul" dibatalkan.',
    ],

    'transaction_expired' => [
        'title' => 'Transaksi kedaluwarsa',
        'body' => 'Transaksi untuk ":judul" kedaluwarsa tanpa tindak lanjut.',
    ],
];
