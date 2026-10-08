<?php

declare(strict_types=1);

return [
    'title' => 'Rating Inspektor',
    'inspector_label' => 'Inspektor:',
    'rating' => 'Rating',
    'score' => 'Skor Rating',
    'comment' => 'Komentar / Ulasan',
    'comment_placeholder' => 'Tulis ulasan Anda mengenai ketelitian dan pelayanan inspektor (opsional, maks 500 karakter)...',
    'comment_hint' => 'Maksimal 500 karakter. Kode HTML akan dibersihkan secara otomatis.',
    'submit' => 'Kirim Rating',
    'submitting' => 'Mengirim Rating...',
    'success' => 'Terima kasih, rating Anda berhasil dikirim dan tidak dapat diubah.',
    'already_rated' => 'Anda telah memberikan rating untuk transaksi ini.',
    'no_inspector' => 'Transaksi ini tidak memiliki inspeksi berbayar sehingga tidak ada inspektor untuk dinilai.',
    'not_completed' => 'Rating hanya dapat diberikan setelah transaksi selesai.',
    'unauthorized' => 'Hanya pembeli dari transaksi ini yang berhak memberikan rating.',
    'cannot_modify' => 'Rating tidak dapat diubah atau dihapus setelah dikirim.',
    'character_count' => ':count/500 karakter',

    'admin' => [
        'title' => 'Moderasi Rating',
        'heading' => 'Daftar Rating Transaksi',
        'subheading' => 'Kelola visibilitas rating inspektor yang melanggar ketentuan.',
        'transaction' => 'Transaksi',
        'inspector' => 'Inspektor',
        'buyer' => 'Pembeli',
        'score' => 'Skor',
        'comment' => 'Komentar',
        'status' => 'Status',
        'action' => 'Aksi',
        'hide' => 'Sembunyikan',
        'unhide' => 'Tampilkan',
        'hidden_success' => 'Rating berhasil disembunyikan.',
        'unhidden_success' => 'Rating berhasil ditampilkan kembali.',
        'status_visible' => 'Tampil',
        'status_hidden' => 'Disembunyikan',
        'empty' => 'Belum ada rating yang terdaftar.',
        'filter_all' => 'Semua',
        'filter_visible' => 'Aktif',
        'filter_hidden' => 'Disembunyikan',
        'timezone' => 'WIB',
    ],

    'errors' => [
        'not_completed' => 'Rating hanya dapat diberikan pada transaksi yang sudah selesai.',
        'unauthorized' => 'Hanya pembeli dari transaksi yang berhak memberikan rating.',
        'already_submitted' => 'Rating untuk transaksi ini sudah pernah dikirim.',
        'no_inspector' => 'Transaksi pada listing tanpa hasil inspeksi tidak dapat dirating.',
        'invalid_score' => 'Skor rating harus berupa angka 1 sampai 5.',
        'invalid_comment' => 'Komentar tidak boleh melebihi 500 karakter.',
    ],

    'display' => [
        'rating_summary' => ':avg (:count ulasan)',
        'rating_count' => ':count ulasan',
        'no_ratings' => 'Belum ada rating',
        'out_of_five' => ':score dari 5 bintang',
    ],
];
