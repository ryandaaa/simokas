# 0007 — Brand Primer Hijau (Amandemen Palet ClassiAds)

Tanggal: 2026-10-12. Status: Diterima.

## Konteks

ADR 0006 mengadopsi ClassiAds dengan primer Electric Blue `#2563EB` dan
sekunder Deep Indigo `#4F46E5`. Implementasi berjalan (`ds-*` dipakai 430+
titik), tetapi brand produk yang disepakati adalah **hijau**. Dokumen acuan
masih menyebut biru sehingga berisiko "diperbaiki" kembali oleh developer
berikutnya.

## Keputusan

1. Primer brand: Forest Green `#16A34A` (`--color-ds-blue`, utilitas
   `bg-green-600` / `text-green-600`). Sekunder: Emerald `#059669`
   (`--color-ds-indigo`, gradien `from-green-600 to-emerald-600`).
2. Varian `soft`/`ink` mengikuti rumpun hijau yang sudah ada
   (`ds-blue-soft` `#DCFCE7`, `ds-blue-ink` `#15803D`) — tidak diubah.
3. Aksen kuning `#FACC15`, kanvas `#F8FAFC`, dan rumpun `ds-green` (status
   sukses) tidak berubah.
4. `docs/design.md`, `docs/design.html`, dan ADR 0006 (catatan amandemen)
   diselaraskan; riwayat ADR 0006 tidak ditulis ulang.

## Konsekuensi

- Tidak ada perubahan kode runtime (token sudah hijau sejak awal).
- GuardrailsTest tidak berubah (hanya melarang warna Tailwind mentah).
- Kontras teks primer di atas putih ±8,6:1 — lolos WCAG AA/AAA.
