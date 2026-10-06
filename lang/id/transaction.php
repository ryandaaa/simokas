<?php

declare(strict_types=1);

return [
    'status' => [
        'offered' => 'Penawaran',
        'accepted' => 'Diterima',
        'paid_held' => 'Dana Ditahan',
        'completed' => 'Selesai',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
        'expired' => 'Kedaluwarsa',
    ],
    'errors' => [
        'cannot_buy_own_listing' => 'Anda tidak dapat membeli listing milik sendiri.',
        'inspector_cannot_buy' => 'Akun inspektor tidak dapat membeli listing. Gunakan akun pengguna terpisah.',
        'transaction_already_running' => 'Listing ini sudah memiliki transaksi berjalan. Hanya satu transaksi aktif per listing.',
        'invalid_listing_status' => 'Penawaran hanya dapat diajukan pada listing Aktif atau Sedang Diinspeksi.',
        'not_authorized' => 'Anda tidak memiliki hak akses untuk tindakan ini.',
    ],
];
