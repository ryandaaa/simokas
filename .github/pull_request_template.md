## Ringkasan

<!-- Apa yang berubah dan mengapa. Satu domain per PR bila memungkinkan. -->

## Domain dan flow

- Domain:
- Flow doc / fitur: `docs/flows/....md`
- Test case yang tercakup (ID): `XXX-T01`, `XXX-T02`

## Cara menguji

<!-- Langkah manual singkat atau perintah test. -->

## Tangkapan layar (perubahan UI)

<!-- Lebar 360 dan 1280 px, termasuk keadaan kosong dan gagal. -->

## Daftar periksa

- [ ] Unit test untuk setiap Action, Policy, Enum, dan Rule baru
- [ ] Feature test untuk setiap rute, komponen Livewire, Listener, dan command baru
- [ ] Otorisasi diuji untuk semua role, data persis tidak bocor
- [ ] `composer check` lulus
- [ ] UI memakai token dan komponen bersama, ada keadaan memuat, kosong, dan gagal
- [ ] Flow doc dan dokumen terkait diperbarui bila perilaku berubah
- [ ] Batas domain tidak dilanggar (arch test hijau)
- [ ] Tidak ada rahasia, `dd()`, atau kode mati

## Catatan untuk reviewer

<!-- Bagian yang perlu perhatian khusus, [OPEN] baru, atau keputusan yang diambil. -->
