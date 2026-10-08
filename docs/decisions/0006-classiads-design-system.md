# 0006 — Adopsi ClassiAds Design System untuk Dashboard

Tanggal: 2026-10-08. Status: Diterima (dashboard dulu, halaman lain menyusul).

## Konteks

Tim memasukkan `docs/design.html` + `docs/design.md` (ClassiAds: biru `#2563EB`, kuning
`#FACC15`, kanvas `#F8FAFC`, font Plus Jakarta Sans + Inter, ikon FontAwesome)
sebagai acuan visual. Sistem lama (token forest/lime, font Satoshi) tetap
dipakai halaman lain sampai migrasinya tuntas.

## Keputusan

1. Token ClassiAds didefinisikan sebagai token baru berawalan `ds-*` di
   `resources/css/app.css` (hex persis `docs/design.md`), BUKAN menimpa token lama,
   agar halaman yang belum migrasi tidak berubah.
2. View domain dilarang memakai kelas warna Tailwind mentah (GuardrailsTest),
   sehingga view dashboard memakai token `ds-*`.
3. Font Plus Jakarta Sans + Inter dan FontAwesome dimuat di layout head
   (aditif). Body memakai `.ds-sans` + kanvas `#F8FAFC`.
4. Header, footer, nav HP, dan skip-link layout ditulis ulang mengikuti
   `docs/design.html` (logo gradien, footer slate-900). Menu akun ikut diselaraskan
   (link aktif biru) beserta ekspektasi LayoutTest.
5. `x-ui.*` tidak diubah; halaman dashboard memakai markup mentah ClassiAds.

## Konsekuensi

- `docs/UI_GUIDELINES.md` perlu direvisi saat migrasi halaman lain dimulai.
- Test `LayoutTest` (menu akun) dan `ComponentRenderTest` (x-ui) tetap hijau.
- Halaman non-dashboard untuk sementara campuran: font/kanvas baru,
  komponen lama.
