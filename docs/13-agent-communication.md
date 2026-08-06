# Komunikasi Antar-Agent

## Prinsip

Handoff adalah catatan durable, bukan chat sementara. Buat handoff pada perpindahan
pemilik, perubahan schema/API/keputusan, masuk review, atau saat blocked. Gunakan
task ID yang sama, path relatif repo, commit bila ada, dan evidence yang dapat
direproduksi. Jangan sertakan secret, token, data pribadi, atau exploit berbahaya.

## Format kanonis

```yaml
handoff:
  task_id: TASK-000
  from_agent: ""
  to_agent: ""
  context: ""
  files_changed: []
  database_changes: []
  api_changes: []
  decisions: []
  assumptions: []
  tests_performed: []
  known_issues: []
  required_next_action: []
  evidence: []
```

`database_changes` mencakup migration, compatibility, backfill, lock, dan rollback;
`api_changes` mencakup versi, breaking/non-breaking, consumer, dan OpenAPI;
`decisions` merujuk ADR; asumsi harus ditandai belum disetujui; test menyebut command,
environment, hasil; known issue menyebut severity/owner; next action harus spesifik.

## Protokol penerimaan

Penerima memeriksa kelengkapan, membuka diff/evidence, mengonfirmasi dependency dan
scope, lalu menyatakan `accepted` atau `returned` beserta alasan. Handoff tidak
lengkap dikembalikan; conflict tidak diselesaikan diam-diam. Perubahan kontrak
mewajibkan notifikasi kepada Backend, Frontend, QA, Architect, dan Orchestrator.
Blocker segera dieskalasikan dengan dampak, opsi, dan keputusan yang dibutuhkan.

Orchestrator mencatat hasil pada status task dan project memory. Fakta temporer
tetap di task/handoff; hanya keputusan, keadaan rilis, issue, dan pengetahuan lintas
sesi yang telah diverifikasi dipromosikan ke memory.
