# Panduan Inspektor SIMOKAS

## 1. Daftar dan verifikasi
1. Buka Daftar dan pilih akun **Inspektor**.
2. Isi nama, email, nomor HP, password, jenis keahlian, dan pengalaman.
3. Unggah KTP, sertifikat pendukung, CV, dan portofolio sesuai form.
4. Dokumen disimpan privat dan diperiksa admin.
5. Status **Menunggu Verifikasi** mengunci fitur job.
6. Setelah disetujui admin, Anda dapat melihat dan mengambil job.
7. Jika ditolak, periksa alasan dan ajukan ulang dokumen.
> [Gambar: Halaman pendaftaran inspektor, pilihan akun dan dokumen verifikasi, rute pendaftaran inspektor]

## 2. Melihat dan mengambil job
1. Buka daftar job yang tersedia.
2. Ambil hanya job yang sesuai keahlian kendaraan Anda.
3. Maksimal **3 job aktif** dapat Anda pegang.
4. Anda tidak dapat mengambil job dari listing milik sendiri.
5. Tekan **Ambil Job**. Pengambilan bersifat atomik.
6. Jika sudah diambil orang lain, job tidak dapat Anda ambil.
7. Setelah berhasil, alamat persis dan nomor rangka dapat terlihat.
> [Gambar: Daftar job terbuka dengan filter dan tombol Ambil Job, rute `/jobs/open`]

## 3. Menjadwalkan dan memulai inspeksi
1. Hubungi penjual melalui percakapan yang tersedia.
2. Sepakati waktu dari opsi yang diberikan penjual.
3. Tetapkan jadwal final pada detail job.
4. Setelah jadwal ditetapkan, mulai inspeksi.
5. Jangan mulai sebelum jadwal ditetapkan.
6. Jika penjual tidak responsif, job dapat ditandai perlu tindakan setelah 48 jam.
7. Jika Anda membatalkan sebelum inspeksi dimulai, job kembali ke daftar.
> [Gambar: Detail job dengan jadwal, chat, dan tombol Mulai Inspeksi, rute `/jobs/{id}`]

## 4. Mengisi checklist dan draf
1. Form memakai template sesuai jenis kendaraan.
2. Isi kategori satu per satu.
3. Nilai setiap item **Baik, Cukup, atau Buruk**.
4. Tambahkan catatan bila diperlukan.
5. Ambil foto jika item mewajibkannya.
6. Saat berpindah kategori, draf tersimpan otomatis di server.
7. Draf dapat dilanjutkan setelah halaman dimuat ulang.
> [Gambar: Form inspeksi per kategori dengan pilihan Baik/Cukup/Buruk, rute `/jobs/{id}/fill`]

## 5. Mengambil foto
1. Foto inspeksi wajib diambil langsung dari kamera.
2. Foto dari galeri ditolak.
3. Foto diberi cap waktu dan ID job pada gambar.
4. Waktu server menjadi acuan.
5. Ukuran foto maksimal **2 MB**.
6. Foto disimpan di penyimpanan privat.
7. Jika izin kamera ditolak, aktifkan izin kamera pada browser/perangkat.
8. Tanpa foto wajib, hasil tidak dapat dikirim.
9. Jika foto tetap gagal setelah dikompresi, perbaiki lalu coba lagi.
> [Gambar: Komponen kamera dengan preview foto dan cap waktu, rute `/jobs/{id}/fill`]

## 6. Mengirim hasil
1. Pastikan semua item wajib terisi dan jumlah foto minimal terpenuhi.
2. Tekan **Kirim Hasil**.
3. Sistem menghitung skor dan rekomendasi.
4. Setelah dikirim, hasil terkunci dan tidak dapat diubah.
5. Hanya admin yang dapat membuka kembali hasil.
6. Jika ditolak, lengkapi item atau foto yang disebut pada pesan galat.
> [Gambar: Bagian akhir form dan tombol Kirim Hasil, rute `/jobs/{id}/fill`]

## 7. Fee dan pendapatan
1. Fee cair setelah pemohon menerima hasil.
2. Jika tidak menekan **Terima Hasil**, job otomatis selesai setelah **3 hari**.
3. Pengaturan `auto_accept_days` dapat diubah admin.
4. Setelah job selesai, fee masuk ke saldo simulasi inspektor.
5. Buka **Pendapatan** untuk melihat saldo dan rincian.
> [Gambar: Halaman Pendapatan dengan saldo dan rincian komisi, rute `/earnings`]

## 8. Membatalkan job
1. Sebelum inspeksi dimulai, inspektor dapat membatalkan job.
2. Job kembali **Dibayar** dan tersedia untuk inspektor lain.
3. Penghitung 7 hari dimulai ulang saat job kembali tersedia.
4. Saat `in_progress`, pemohon tidak dapat membatalkan. Admin yang menangani.
5. Setelah hasil dikirim, pemohon harus menerima hasil; admin dapat menyelesaikan atau membatalkan.
6. Jika `paid` tidak diambil selama 7 hari, sistem membatalkan dan mengembalikan 100% dana kepada pemohon.

## Gambar yang perlu ditambahkan
- `/jobs/open` — daftar job terbuka.
- `/jobs/{id}` — detail job, jadwal, dan aksi.
- `/jobs/{id}/fill` — checklist dan kamera.
- `/earnings` — pendapatan.
- `/inspector/register` — pendaftaran dan dokumen verifikasi.
