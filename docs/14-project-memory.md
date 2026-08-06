# Project Memory

**Terakhir diperbarui:** 6 Agustus 2026<br>
**Sumber kebenaran produk:** [`PRD.md`](PRD.md)<br>
**Aturan:** bedakan fakta, asumsi, dan rencana; perbarui setelah gate/release dan
sertakan evidence. Riwayat keputusan tidak dihapus—gunakan status `superseded`.

## Snapshot proyek

| Area | Nilai saat ini | Status/evidence |
| --- | --- | --- |
| Product identity | Sinaesta adalah nama kerja; domain belum dikonfirmasi | Fakta; PRD §1–2 |
| Active stack | Belum dipilih | Blocking; PRD §8–10 |
| Architecture | Belum dirancang | Menunggu PRD disetujui |
| Current release | Belum ada release aplikasi | Fase dokumentasi |
| Database version | N/A; database belum tersedia | Belum dipilih |
| API version | N/A; API belum tersedia | Belum dirancang |
| Completed modules | PRD draf dan audit kode awal | Dokumentasi tersedia |
| In-progress modules | Dokumentasi tata kerja agent/project memory | Prompt 3 |
| Pending modules | Discovery, persona, journey, data/privacy, threat model, UX, architecture, aplikasi | PRD §9–10 |
| Deployment state | Belum ada aplikasi/environment deployment | Tidak berlaku saat ini |
| Last successful test | N/A; belum ada kode atau test suite | Audit kode awal |
| Last deployment | N/A | Belum pernah deployment |
| Current blockers | Domain, owner, persona, flow, metric, data/regulasi, approval PRD | Documentation gate |
| Next priority | Jawab pertanyaan wajib PRD dan dapatkan sign-off | Sebelum desain/implementasi |

## Important decisions / decision log

| ID | Tanggal | Status | Keputusan | Alasan | Dampak/evidence |
| --- | --- | --- | --- | --- | --- |
| DEC-001 | 2026-08-06 | accepted | Tahan backend bisnis sampai documentation gate PRD terpenuhi | Mencegah implementasi berbasis asumsi | `PRD.md` §9 |
| DEC-002 | 2026-08-06 | accepted | Domain dan active stack tetap TBD, bukan diinferensikan dari nama/prompt agent | Belum ada brief atau kebutuhan tervalidasi | `PRD.md` §2; `LEGACY_CODE_AUDIT.md` |

Decision baru wajib memuat ID, tanggal, owner/approver, context, pilihan, keputusan,
konsekuensi, link ADR/evidence, dan status `proposed`, `accepted`, `superseded`, atau
`rejected`. Keputusan teknis besar dicatat sebagai ADR lalu diringkas di sini.

## Known-issue table

| ID | Severity | Issue | Dampak | Owner | Mitigasi/next action | Status |
| --- | --- | --- | --- | --- | --- | --- |
| KI-001 | High | Domain, persona, dan alur utama belum ditentukan | Scope/solusi tidak dapat divalidasi | Product owner TBD | Discovery dan persetujuan PRD | Open/blocking |
| KI-002 | High | Inventaris data, privasi, dan regulasi belum tersedia | Desain keamanan/data berisiko salah | Product/Security owner TBD | Klasifikasi data dan review | Open/blocking |
| KI-003 | Medium | Stack, test, CI/CD, dan environment belum tersedia | Implementasi/release belum dapat diverifikasi | Engineering owner TBD | Pilih setelah kebutuhan stabil | Open |

## Security findings, technical debt, dan operasi

- **Security findings:** belum ada assessment aplikasi karena aplikasi belum ada;
  ketiadaan review bukan bukti aman. Threat model pending setelah flow/data jelas.
- **Technical debt:** fondasi kode, test, CI, configuration, scanning, migration,
  backup/restore, observability, dan ownership belum dibangun; saat ini tercatat
  sebagai enablement pending, bukan defect implementasi.
- **Deployment:** tidak ada artifact, environment, backup, atau deployment.

## Prosedur pembaruan

Reporter mengusulkan perubahan; Orchestrator memverifikasi terhadap PRD, ADR,
task/handoff, CI, QA/security report, atau deployment evidence. Perbarui snapshot,
decision log, known issue, blocker, next priority, last successful test/deployment,
dan tanggal secara atomik. Jangan menandai planned sebagai completed, jangan menulis
credential, dan arsipkan issue hanya setelah verification evidence tersedia.
