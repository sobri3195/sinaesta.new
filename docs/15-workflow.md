# Workflow dan Quality Gate

## Status kanonis

`Proposed` → `Ready` → `Planned` → `In Progress` → `In Review` → `QA` →
`Security Review` → `Ready for Staging` → `UAT` → `Ready for Production` →
`Released`.

Status pengecualian: `Blocked` (dependency/keputusan menghalangi), `Rejected`
(tidak layak/out of scope), dan `Rolled Back` (rilis dibatalkan setelah deploy).
Status tidak boleh dilompati tanpa waiver tertulis dari owner gate dengan alasan,
masa berlaku, kontrol kompensasi, dan evidence.

## Quality gate per perpindahan

| Transisi | Gate wajib |
| --- | --- |
| Proposed → Ready | Terlacak ke PRD; objective/scope/non-scope dan acceptance criteria jelas; pertanyaan blocking ditutup; documentation gate berlaku. |
| Ready → Planned | Planner menghasilkan decomposition, dependency DAG, risiko, edge case, test scenario, rollback, dan kompleksitas; owner tersedia. |
| Planned → In Progress | Desain/ADR/kontrak yang diperlukan disetujui; input/environment siap; dependency selesai; file ownership dan urutan bebas konflik. |
| In Progress → In Review | Acceptance criteria diimplementasikan; self-check/test relevan lulus; diff terbatas; migration/OpenAPI/docs dan handoff diperbarui; tidak ada secret. |
| In Review → QA | Review independen selesai; temuan ditangani; build/commit immutable tersedia; test plan, data, dan environment reproducible siap. |
| QA → Security Review | Functional/integration/regression/contract dan matriks target selesai dengan evidence; defect triaged; rekomendasi QA bukan `no-go`. |
| Security Review → Ready for Staging | Threat model/review selesai; tidak ada Critical/High terbuka; temuan lain dimitigasi atau diterima resmi; security evidence tersedia. |
| Ready for Staging → UAT | Artifact yang sama terdeploy; migration, config, health, smoke, observability, backup dan rollback check lulus; release notes tersedia. |
| UAT → Ready for Production | Acceptance bisnis ditandatangani; alur dan data representatif lulus; known limitation/risiko residual diterima; support/runbook siap. |
| Ready for Production → Released | Approval dan change window ada; artifact/provenance benar; backup dan rollback owner siap; deploy, migration, smoke, health, monitoring lulus. |
| Released → Rolled Back | Trigger rollback terpenuhi; incident owner menyetujui; rollback aplikasi/data/config dijalankan dan diverifikasi; stakeholder diberi tahu. |

## Status pengecualian dan re-entry

- Ke `Blocked`: catat blocker, dampak, owner, dependency, evidence, dan syarat buka.
  Setelah teratasi, kembali ke status terakhir dan ulangi gate yang mungkin basi.
- Ke `Rejected`: Orchestrator/owner mencatat alasan (duplikat, tidak bernilai, tidak
  aman, atau out of scope). Re-entry dimulai dari `Proposed` dengan konteks baru.
- Dari `Rolled Back`: buat incident/bug, lindungi data dan layanan, lakukan RCA serta
  regression test; perubahan berikutnya kembali melalui `Planned`, bukan langsung
  production.
- Jika scope, requirement, architecture, schema, atau API berubah material, task
  mundur ke gate paling awal yang terdampak. Evidence lama tidak otomatis berlaku.

## Definition of evidence dan kewenangan

Evidence minimal menyebut task/requirement, commit/build, environment, command atau
langkah, hasil, timestamp, dan artefak tereduksi. Author tidak menjadi satu-satunya
approver. Orchestrator mengelola status; Planner menilai kesiapan rencana; Architect
menyetujui konsistensi desain; reviewer menilai implementasi; QA dan Security
memberi gate independen; Product owner menyetujui UAT; DevOps/release owner menjaga
staging/production. Reporter merangkum, bukan memberi approval.

Emergency change tetap membutuhkan task, peer/security review proporsional,
backup/rollback, smoke/monitoring, dan post-change review; urgensi tidak menghapus
audit trail.
