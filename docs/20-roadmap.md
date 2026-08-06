# Roadmap SINAESTA

**Status:** Proposed; urutan berbasis dependency, bukan janji tanggal  
**Horizon:** Phase 0 sampai Phase 5

Roadmap mempromosikan artifact hanya setelah acceptance dan exit criteria phase
terpenuhi. Scope baru tidak boleh disisipkan tanpa triage dampak terhadap PRD,
arsitektur, keamanan, test, operasi, dan jadwal. Feature flag tidak menggantikan
quality gate. Metrik baseline dan target kuantitatif ditetapkan Product Owner pada
planning setiap phase.

## Phase 0 — Foundation

### Objective

Membentuk sumber kebenaran produk dan fondasi engineering sehingga implementasi
MVP dapat dimulai tanpa requirement, kontrak, atau jalur rilis yang ambigu.

### Scope

- PRD, dokumentasi agent/workflow, arsitektur modular monolith, ADR, threat model.
- Struktur repository frontend/backend/infrastructure/test.
- Kontrak API awal, schema/migration strategy, coding dan testing standard.
- CI untuk format, lint, static analysis, typecheck, unit, contract, build, secret
  scan, dependency scan, dan artifact provenance.

### Dependencies

Product owner dan stakeholder domain; keputusan hosting/runtime/provider; owner
engineering, QA, Security, dan DevOps; akses repository/CI/staging.

### Risks

PRD belum tervalidasi, over-design sebelum alur pengguna pasti, keterbatasan shared
hosting, versi TanStack berubah, dan ownership/gate hanya terdokumentasi tanpa
enforcement. Mitigasi: documentation gate, spike terukur, lockfile, ADR, dan CI
required checks.

### Deliverables

PRD/architecture/standards disetujui, repository skeleton, OpenAPI baseline,
environment example, migration awal, CI workflow, review policy, staging plan,
observability/backup/rollback runbook, serta backlog Phase 1 terestimasi.

### Acceptance criteria

- Requirement MVP, non-scope, role, alur, data sensitivity, dan acceptance scenario
  disetujui dengan pertanyaan blocking ditutup.
- Dependency direction dan struktur dapat dijelaskan; sample skeleton build dan
  test berjalan reproducibly dari clean checkout.
- CI memblokir pelanggaran minimum dan tidak membocorkan secret.
- Staging/deploy/backup-restore/rollback approach telah direview owner terkait.

### Exit criteria

Documentation gate berstatus approved; ADR/contract baseline diberi versi; seluruh
deliverable memiliki owner; risiko Critical/High dimitigasi/diterima resmi; backlog
Phase 1 memenuhi Definition of Ready dan tidak ada blocker fondasi.

## Phase 1 — MVP

### Objective

Menyediakan perjalanan belajar dasar yang aman: pengguna masuk, mengelola profil,
memilih soal, berlatih/quiz, dan menerima hasil dasar; admin mengelola soal.

### Scope

- Authentication dan session; profile.
- Question bank dengan draft/review/publish/version dasar.
- Practice, basic quiz, snapshot soal, penyimpanan jawaban, submit.
- Basic result yang dihitung server.
- Admin questions dengan role/ownership, audit, pagination, dan validasi.

### Dependencies

Phase 0 selesai; taxonomy/content/rubric dan role matrix disetujui; design/accessibility
baseline; email service bila verifikasi/reset termasuk MVP; data seed non-produksi.

### Risks

Kebocoran answer key, account enumeration/session attack, konten soal tidak siap,
aturan scoring ambigu, authorization admin terlalu luas, dan hasil tidak dapat
direproduksi. Mitigasi melalui snapshot/version, server-side policy/scoring,
security test, rate limit, audit, dan review konten.

### Deliverables

UI/API/database untuk scope, OpenAPI/migration, admin workflow, fixture/seed aman,
unit-domain-repository-API-component-E2E/security tests, dashboard/log/health,
user/admin guide, dan deployment release candidate.

### Acceptance criteria

- User dapat register/login/logout, mengubah profil sendiri, memulai practice/quiz,
  menyimpan dan submit jawaban, lalu melihat hasilnya sendiri.
- Admin berizin dapat membuat, mereview, mempublikasikan, dan memversion soal;
  pengguna tidak menerima answer key sebelum diizinkan.
- Scoring berasal dari snapshot server dan reproducible; role/object authorization,
  validation, error, audit, accessibility keyboard, serta recovery path lulus.
- Target coverage Authentication 95%, Question workflow 90%, Scoring critical-path
  100%, dan modul lain 80% terpenuhi dengan test berkualitas.

### Exit criteria

UAT perjalanan MVP ditandatangani; tidak ada Critical/High terbuka; data/content
production approved; backup/restore dan rollback rehearsal lulus; smoke/monitoring/
support runbook siap; KPI baseline direkam; release stabil selama observation window.

## Phase 2 — CBT

### Objective

Menghadirkan pengalaman ujian terkontrol dan tahan gangguan untuk mini serta full
tryout dengan waktu dan status otoritatif di server.

### Scope

- Mini tryout dan full tryout, jadwal serta attempt limit.
- Server timer/deadline, autosave revision/idempotency, restore, dan auto-submit.
- Question navigator, penanda status, review sebelum submit, result sesuai policy.
- Concurrency handling, background expiry job, audit dan recovery.

### Dependencies

Phase 1 stabil; aturan tryout/durasi/result disetujui; kapasitas DB/worker/cron;
sinkronisasi clock; dataset dan traffic model; design mobile/keyboard.

### Risks

Race autosave-submit, koneksi putus, clock klien dimanipulasi, traffic serentak,
duplicate attempt, data jawaban hilang, dan auto-submit terlambat. Mitigasi:
deadline server, row lock/unique constraint, revision/idempotency, recovery job,
load test, telemetry, dan komunikasi UX yang jelas.

### Deliverables

Tryout API/UI, attempt state machine, autosave/restore/auto-submit worker, navigator
dan review, integration/E2E/concurrency/performance tests, capacity plan, dashboard
attempt/queue, incident and recovery runbook.

### Acceptance criteria

- Mini/full tryout hanya dapat dimulai sesuai schedule/limit; deadline yang sama
  dipulihkan lintas reload/device dan tidak dapat diperpanjang klien.
- Autosave idempoten dan conflict terlihat; refresh/offline sementara tidak
  menghilangkan jawaban yang telah diakui server.
- Submit manual dan otomatis menghasilkan tepat satu hasil dari snapshot; akses
  setelah deadline mengikuti policy.
- Load target yang disepakati memenuhi latency/error SLO; keyboard/mobile flow,
  monitoring, dan failure injection utama lulus.

### Exit criteria

Rehearsal tryout representatif lulus tanpa kehilangan/duplikasi data; capacity dan
on-call sign-off; recovery/rollback diuji; seluruh defect release-blocking tutup;
UAT dan security/performance gate menyetujui produksi.

## Phase 3 — Monetization

### Objective

Memungkinkan pembelian yang akurat, dapat diaudit, dan idempoten serta memberikan
akses hanya berdasarkan entitlement yang sah.

### Scope

- Packages sebagai data server, entitlement, voucher dan usage limits.
- Payment creation/provider integration/webhook/reconciliation.
- Invoice/receipt, subscription activation dan expiry.
- Admin/support view terbatas, audit, refund/cancel flow bila disetujui PRD.

### Dependencies

Phase 2/stable identity; keputusan produk/harga/pajak/refund; kontrak provider dan
sandbox; legal/privacy/finance review; mail; cron/queue; observability dan support.

### Risks

Nominal salah, webhook palsu/replay/out-of-order, double charge/grant, voucher race,
provider outage, expiry timezone, invoice compliance, PII exposure, dan dispute.
Mitigasi dengan amount server, signature/replay protection, idempotency/lock,
state machine, outbox/reconciliation, UTC, audit, least privilege, dan runbook.

### Deliverables

Package/voucher/payment/subscription/entitlement/invoice UI/API/schema, adapter
provider, signed webhook, reconciliation/expiry jobs, finance report, audit/support
tools, sandbox E2E, security tests, alerts, refund/dispute/reconciliation runbook.

### Acceptance criteria

- User melihat package/harga dari backend, membuat payment dengan nominal/currency
  benar, menerima invoice, dan memperoleh entitlement tepat sekali setelah status
  provider yang valid.
- Duplicate, replay, invalid signature, amount mismatch, timeout, dan out-of-order
  event tidak menyebabkan double grant atau state regression.
- Voucher limit aman terhadap concurrency; expiry subscription/entitlement memakai
  waktu server dan segera menutup akses sesuai policy.
- Payment dan entitlement critical path 100% teruji; finance dapat merekonsiliasi
  provider, ledger internal, invoice, dan audit tanpa mengakses secret.

### Exit criteria

Legal/finance/security/UAT sign-off; sandbox dan controlled live transaction serta
refund/reconciliation lulus; alert/support/escalation aktif; backup/restore menjaga
record finansial; tidak ada discrepancy tak terjelaskan atau defect blocking.

## Phase 4 — Analytics

### Objective

Memberi insight belajar yang akurat dan dapat ditindaklanjuti tanpa mengorbankan
privasi atau mengubah sumber nilai/attempt.

### Scope

- Topic, difficulty, dan time analytics.
- Wrong-answer practice, bookmark, progress history.
- Aggregation/versioning, privacy/access controls, export/retention bila disetujui.

### Dependencies

Attempt/scoring/content taxonomy berkualitas dan berversi; analytics event/data
dictionary; consent/privacy/retention policy; query/index/aggregation infrastructure;
design visualisasi yang accessible.

### Risks

Insight menyesatkan akibat taxonomy/sample kecil, query mahal, bocor data antar
user, bookmark terhadap versi soal usang, metric definition drift, dan over-retention.
Mitigasi: definisi metrik berversi, minimum sample/label, owner, aggregate job,
authorization, lineage/reconciliation, index/cache, dan deletion policy.

### Deliverables

Metric catalog/data dictionary, aggregation jobs, analytics/progress/bookmark/
wrong-answer API dan UI, data quality checks, privacy/security/performance tests,
dashboard freshness/lag, serta user explanation.

### Acceptance criteria

- Setiap metrik memiliki formula, source, timezone/window, version, owner, dan test;
  agregat dapat direkonsiliasi ke attempt/scoring sample.
- User hanya melihat data sendiri; admin hanya scope/agregat yang disetujui dan
  threshold privasi diterapkan.
- Bookmark dan wrong-answer practice menangani question version/publication dengan
  aman; tidak membocorkan answer key aktif.
- Query memenuhi SLO dataset representatif; empty/insufficient/stale data dan
  visualisasi keyboard/screen-reader mempunyai UX eksplisit.

### Exit criteria

Product/data/privacy/UAT sign-off; data quality dan freshness stabil untuk periode
observasi; SLO/alerts/runbook/retention aktif; tidak ada privacy/security blocker;
metrik adopsi dan outcome baseline tersedia.

## Phase 5 — Scale

### Objective

Meningkatkan kapasitas, efisiensi operasi, dan kemampuan institusi tanpa merusak
isolasi tenant, correctness, atau kualitas pengalaman inti.

### Scope

- Caching dan performance optimization berbasis profiling.
- Institution package dan scoped tenant administration.
- Bulk import dengan preview/validation/idempotency/error report.
- Advanced reporting/export, monitoring matang, load/stress/endurance testing.

### Dependencies

SLO dan traffic forecast; hasil profiling; tenant/isolation/billing/privacy model;
format import/report dan retention; queue/object storage/cache infrastructure;
capacity budget serta on-call maturity.

### Risks

Cache stale pada entitlement/result, cross-tenant leakage, noisy neighbor, import
merusak data, formula report berbeda, export PII, queue/backpressure, cost growth,
dan optimasi prematur. Mitigasi: cache key/version/invalidation, tenant policy pada
setiap query, quotas, staged import, immutable audit, reconciliation, encryption,
profiling dan capacity alert.

### Deliverables

Cache/capacity design, tenant/institution model dan admin, import pipeline/report,
advanced reporting/export, SLO/error budget, dashboards/alerts/traces, chaos/failure
drills, load/endurance reports, capacity/cost plan, dan scale incident runbooks.

### Acceptance criteria

- Target concurrency/dataset memenuhi SLO dan error budget pada load/endurance test
  representatif tanpa correctness regression.
- Cache failure/staleness tidak memberi akses salah; invalidation dan fallback
  teruji serta observable.
- Tenant isolation lolos negative authorization/security test pada API, job, cache,
  report, export, dan admin; quotas mencegah noisy neighbor.
- Bulk import melakukan dry-run/preview, validasi per baris, idempotency, partial
  failure report dan audit; report dapat direkonsiliasi ke sumber data.
- Monitoring mendeteksi failure yang disimulasikan dan on-call mengeksekusi runbook
  dalam target respons.

### Exit criteria

Capacity/security/privacy/institution UAT sign-off; load dan disaster/failure drill
lulus; cost/capacity headroom disetujui; SLO/error budget serta on-call beroperasi;
tidak ada isolation/correctness defect blocking; dokumentasi support dan roadmap
operasi berkelanjutan diserahterimakan.

## Governance lintas phase

Pada akhir setiap phase, Reporter mencatat requirement-to-deliverable traceability,
metric before/after, keputusan/ADR, test/security evidence, known limitation,
operational readiness, dan residual risk. Scope yang ditunda kembali ke backlog
dengan owner dan alasan—tidak dianggap selesai diam-diam. Phase berikutnya boleh
melakukan discovery paralel, tetapi implementasi yang bergantung pada exit criteria
belum boleh dirilis sebelum phase pendahulu lulus.
