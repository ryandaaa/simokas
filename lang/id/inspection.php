<?php

declare(strict_types=1);

return [
    'status' => [
        'requested' => 'Menunggu Pembayaran',
        'paid' => 'Menunggu Inspektor',
        'taken' => 'Diambil',
        'scheduled' => 'Dijadwalkan',
        'in_progress' => 'Sedang Diinspeksi',
        'result_submitted' => 'Menunggu Konfirmasi',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'expired' => 'Kedaluwarsa',
    ],
    'errors' => [
        'inspector_forbidden' => 'Akun inspektor tidak dapat mengajukan inspeksi. Gunakan akun pengguna terpisah.',
        'admin_forbidden' => 'Admin tidak dapat mengajukan inspeksi atas nama orang lain.',
        'blocked_forbidden' => 'Akun Anda diblokir sehingga tidak dapat mengajukan inspeksi.',
        'listing_not_active' => 'Inspeksi hanya dapat diajukan pada listing Aktif (status saat ini: :status).',
        'listing_has_active_job' => 'Listing ini sudah memiliki job inspeksi aktif. Tunggu hingga job selesai, batal, atau kedaluwarsa.',
        'invalid_transition' => 'Perubahan status job dari :from ke :to tidak diperbolehkan.',
        'already_processed' => 'Job ini sudah diproses (status saat ini: :status).',
    ],
    'request' => [
        'title' => 'Ajukan Inspeksi',
        'subtitle' => 'Periksa kembali ringkasan sebelum membuat tagihan.',
        'fee_label' => 'Tarif inspeksi',
        'expires_label' => 'Tagihan berlaku sampai',
        'warning_title' => 'Perhatian sebelum memesan',
        'warning' => 'Biaya dipotong sesuai tarif saat ini dan tidak berubah setelah dipesan. Tagihan kedaluwarsa dalam 30 menit bila tidak dibayar.',
        'confirm_title' => 'Buat tagihan inspeksi?',
        'confirm_description' => 'Job inspeksi akan dibuat dan Anda diarahkan ke halaman pembayaran simulasi.',
        'confirm_yes' => 'Ya, Buat Tagihan',
        'cancel' => 'Batal',
        'submit' => 'Ajukan Inspeksi',
        'submitting' => 'Membuat tagihan...',
        'existing_title' => 'Anda sudah memiliki tagihan aktif untuk listing ini.',
        'continue_to_payment' => 'Lanjut ke Pembayaran',
    ],
    'mine' => [
        'title' => 'Inspeksi Saya',
        'subtitle' => 'Daftar pengajuan inspeksi Anda beserta statusnya.',
        'empty_title' => 'Belum ada pengajuan inspeksi',
        'empty_desc' => 'Cari kendaraan di katalog, lalu tekan Ajukan Inspeksi pada listing Aktif.',
        'browse' => 'Jelajahi Katalog',
        'pay' => 'Bayar',
        'view_payment' => 'Lihat Tagihan',
        'retry' => 'Coba lagi',
        'load_failed' => 'Gagal memuat daftar inspeksi. Silakan coba lagi.',
    ],
];
