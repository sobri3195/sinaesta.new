# Sinaesta

Repositori kini memiliki fondasi backend PHP native untuk API, health check, dan
autentikasi di [`backend/`](backend/README.md). Modul bisnis lain tetap mengikuti
*documentation gate* di [`docs/PRD.md`](docs/PRD.md).

## Dokumen dasar

- [`docs/PRD.md`](docs/PRD.md) — tujuan produk, asumsi, MVP, batasan, risiko, dan
  kriteria kesiapan implementasi.
- [`docs/LEGACY_CODE_AUDIT.md`](docs/LEGACY_CODE_AUDIT.md) — hasil pemeriksaan
  kondisi kode awal dan daftar pekerjaan teknis yang perlu ditindaklanjuti.
- [`docs/03-orchestrator.md`](docs/03-orchestrator.md) sampai
  [`docs/15-workflow.md`](docs/15-workflow.md) — mandat agent, komunikasi,
  project memory, serta quality gate pengembangan dan rilis.

## Status saat ini

| Area | Status |
| --- | --- |
| Ringkasan masalah dan tujuan | Draf |
| Pengguna dan alur utama | Menunggu konfirmasi pemilik produk |
| Scope MVP dan non-scope | Draf |
| Kriteria penerimaan | Menunggu konfirmasi pemilik produk |
| Arsitektur dan kontrak API | Belum dimulai; menunggu PRD disetujui |
| Fondasi backend dan autentikasi | Tersedia untuk pengujian integration |
| Backend bisnis lainnya | Belum dimulai |

> **Catatan:** Nama repositori belum cukup untuk menentukan domain bisnis secara
> aman. PRD sengaja memisahkan fakta, asumsi, dan pertanyaan terbuka agar tim tidak
> membangun kebutuhan yang belum disepakati.
