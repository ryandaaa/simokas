<?php

declare(strict_types=1);

return [
    'status' => [
        'pending' => 'Menunggu Verifikasi',
        'approved' => 'Terverifikasi',
        'rejected' => 'Ditolak',
    ],

    'vehicle_types' => [
        'car' => 'Mobil',
        'motorcycle' => 'Motor',
    ],

    'onboarding' => [
        'title' => 'Lengkapi Profil & Dokumen Inspektor',
        'subtitle' => 'Lengkapi data keahlian dan unggah dokumen verifikasi untuk mulai mengambil pekerjaan inspeksi.',
        'profile_section' => 'Profil Keahlian',
        'vehicle_types_label' => 'Jenis Keahlian Kendaraan',
        'vehicle_types_hint' => 'Pilih jenis kendaraan yang Anda kuasai untuk diinspeksi.',
        'experience_label' => 'Pengalaman (Tahun)',
        'experience_hint' => 'Lama pengalaman Anda di bidang inspeksi atau mekanik (0–50 tahun).',
        'save_profile' => 'Simpan Profil',
        'profile_saved' => 'Profil keahlian berhasil disimpan.',
        'documents_section' => 'Unggah Dokumen Verifikasi',
        'documents_hint' => 'Semua dokumen disimpan di penyimpanan privat dan hanya dapat dibuka oleh Anda dan admin melalui tautan bertanda tangan.',
        'queue_ready_title' => 'Dokumen Lengkap',
        'queue_ready_message' => 'Semua dokumen wajib telah lengkap. Pengajuan Anda telah masuk ke dalam antrean verifikasi admin.',
        'queue_missing_title' => 'Dokumen Belum Lengkap',
        'queue_missing_message' => 'Pengajuan belum masuk antrean verifikasi. Silakan lengkapi dokumen berikut:',
        'rejected_alert_title' => 'Pengajuan Verifikasi Ditolak',
        'rejected_alert_message' => 'Alasan penolakan: :reason. Anda dapat memperbaiki atau mengganti dokumen, lalu mengajukan kembali.',
        'approved_alert_title' => 'Akun Inspektor Terverifikasi',
        'approved_alert_message' => 'Selamat, akun inspektor Anda telah disetujui. Anda dapat melihat dan mengambil job inspeksi.',
        'status_label' => 'Status Verifikasi Akun',
        'resubmit_button' => 'Ajukan Ulang Verifikasi',
        'resubmitting' => 'Mengajukan ulang...',
        'resubmitted_success' => 'Pengajuan verifikasi berhasil diajukan ulang dan kini menunggu tinjauan admin.',
    ],

    'documents' => [
        'types' => [
            'ktp' => 'Kartu Tanda Penduduk (KTP)',
            'certificate' => 'Sertifikat Keahlian',
            'cv' => 'Curriculum Vitae (CV)',
            'portfolio' => 'Portofolio',
        ],
        'uploaded' => 'Dokumen berhasil diunggah.',
        'deleted' => 'Dokumen berhasil dihapus.',
        'not_found' => 'Berkas dokumen tidak ditemukan.',
        'view' => 'Lihat Dokumen',
        'delete' => 'Hapus',
        'upload_button' => 'Unggah Berkas',
        'save_link_button' => 'Simpan Tautan',
        'choose_file' => 'Pilih Berkas',
        'file_requirements_ktp' => 'Format: JPG, PNG, atau PDF. Maksimal 2 MB.',
        'file_requirements_certificate' => 'Format: PDF, JPG, atau PNG. Maksimal 2 MB per berkas (maks 3 sertifikat).',
        'file_requirements_cv' => 'Format: PDF saja. Maksimal 2 MB.',
        'file_requirements_portfolio' => 'Format: PDF, JPG, atau PNG maks 2 MB, atau tautan URL portofolio.',
        'portfolio_mode_file' => 'Unggah Berkas',
        'portfolio_mode_url' => 'Tautan URL',
        'portfolio_url_placeholder' => 'https://portofolio-anda.com',
        'empty_ktp' => 'Belum ada dokumen KTP yang diunggah.',
        'empty_certificates' => 'Belum ada sertifikat keahlian yang diunggah.',
        'empty_cv' => 'Belum ada berkas CV yang diunggah.',
        'empty_portfolio' => 'Belum ada berkas atau tautan portofolio yang diunggah.',
        'confirm_delete_title' => 'Hapus Dokumen',
        'confirm_delete_description' => 'Apakah Anda yakin ingin menghapus dokumen ini? Tindakan ini tidak dapat dibatalkan.',
        'confirm_delete_button' => 'Ya, Hapus Dokumen',
        'cancel_button' => 'Batal',
        'certificate_count' => ':count dari 3 sertifikat terunggah',
        'open_url' => 'Buka Tautan Portofolio',
        'uploaded_at' => 'Diunggah pada',
    ],

    'validation' => [
        'safe_portfolio_url' => 'Tautan portofolio harus berupa URL yang valid (hanya skema http atau https, memiliki host, tanpa spasi atau karakter kontrol, dan tanpa userinfo).',
    ],

    'errors' => [
        'invalid_transition' => 'Transisi status verifikasi tidak sah dari :from ke :to.',
        'already_processed' => 'Pengajuan verifikasi ini telah diproses oleh admin lain (status saat ini: :status).',
        'cannot_resubmit' => 'Pengajuan tidak dapat diajukan ulang karena status saat ini adalah :status.',
        'incomplete_documents' => 'Tidak dapat menyetujui inspektor karena dokumen wajib belum lengkap (:missing).',
        'missing_rejection_reason' => 'Alasan penolakan wajib diisi.',
        'not_approved' => 'Hanya inspektor yang telah disetujui yang dapat mengakses halaman atau pekerjaan ini.',
    ],
];
