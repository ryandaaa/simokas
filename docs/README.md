# Dokumentasi SIMOKAS

| Dokumen | Isi |
| --- | --- |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Struktur domain, aturan antar-modul, ServiceProvider, event dan kontrak |
| [CONVENTIONS.md](CONVENTIONS.md) | Penamaan, pola kode (Action, Policy, DTO), database, uang, waktu |
| [TESTING.md](TESTING.md) | Aturan wajib unit test dan feature test, cara menulis, ambang cakupan |
| [UI_GUIDELINES.md](UI_GUIDELINES.md) | Token desain, komponen, status, formulir, kamera, aksesibilitas |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Alur git, PR, review, Definition of Done |
| [flows/00-overview.md](flows/00-overview.md) | Peta modul, alur lintas role, peta event |
| `flows/<modul>.md` | Flow per modul: diagram, edge case, daftar test case |
| [BACKLOG.md](BACKLOG.md) | Daftar kartu kanban (ID, test case, prasyarat, jalur paralel) |
| [panduan-inspektor.md](panduan-inspektor.md) | Panduan satu halaman untuk inspektor |
| [qa/daftar-periksa-uji-manual.md](qa/daftar-periksa-uji-manual.md) | Daftar periksa uji manual QA-P0 untuk perangkat dan browser |
| [features/_TEMPLATE.md](features/_TEMPLATE.md) | Template spesifikasi untuk fitur baru |
| [decisions/](decisions/) | Catatan keputusan (ADR) |

## Urutan membaca untuk anggota baru

1. PRD bagian 1, 8, 12, 14.
2. [flows/00-overview.md](flows/00-overview.md).
3. ARCHITECTURE, CONVENTIONS, TESTING.
4. Flow modul yang akan dikerjakan.

## Menandai hal yang belum pasti

- **[OPEN]**: belum diputuskan tim.
- **[USULAN]**: keputusan sementara dari penyusun, belum disetujui tim.
- **[PRD]**: sudah ditetapkan di PRD.

## Hubungan flow, test, dan kode

Setiap edge case di `flows/<modul>.md` punya ID (misalnya `JOB-E3`). Setiap test case punya ID turunannya (misalnya `JOB-T03`). Nama test di Pest memuat ID itu supaya perilaku, dokumen, dan test selalu bisa ditelusuri.
