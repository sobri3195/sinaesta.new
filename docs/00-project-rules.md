# Aturan Proyek SINAESTA

**Status:** Wajib
**Berlaku sejak:** 6 Agustus 2026
**Cakupan:** Seluruh kode, dokumentasi, data, infrastruktur, dan proses rilis SINAESTA.

Kata **WAJIB**, **DILARANG**, dan **HARUS** bersifat normatif. Pengecualian hanya
boleh dibuat melalui ADR yang disetujui product owner, tech lead, dan security.

## 1. Source of truth

Jika artefak berbeda, urutan tanggung jawab berikut menentukan artefak yang harus
diperbaiki; perbedaan tidak boleh diselesaikan diam-diam di implementasi.

| Area | Sumber kebenaran |
| --- | --- |
| Kebutuhan produk | `PRD.md` |
| Keputusan arsitektur | `02-architecture.md` dan ADR di dalamnya |
| Status, keputusan sementara, pekerjaan berikutnya | `14-project-memory.md` |
| Kontrak API | OpenAPI |
| Struktur database | Migration yang telah diterapkan |
| Authorization | Backend |
| Scoring | Backend |
| Entitlement | Backend |
| Status pembayaran | Backend dan hasil webhook provider yang terverifikasi |

Dokumentasi turunan, mock, tipe frontend, dan diagram harus digenerasikan atau
diselaraskan dengan sumbernya. Frontend tidak boleh menjadi otoritas bisnis.

## 2. Larangan mutlak

- Dilarang menggunakan Supabase.
- Dilarang menggunakan framework backend PHP.
- Dilarang menghitung nilai final di frontend.
- Dilarang mengirim jawaban benar ketika attempt aktif.
- Dilarang mempercayai timer browser.
- Dilarang mempercayai harga dari frontend.
- Dilarang menyimpan password plaintext.
- Dilarang menyimpan access token mentah dalam database.
- Dilarang menyimpan secret dalam repository.
- Dilarang membuat query SQL dengan string concatenation.
- Dilarang menggunakan `SELECT *` pada endpoint produksi tanpa alasan yang
  terdokumentasi dan disetujui reviewer.
- Dilarang menonaktifkan test agar pipeline lulus.
- Dilarang menghapus validasi untuk menyelesaikan bug.
- Dilarang membuat `catch` block kosong.
- Dilarang menyembunyikan error tanpa pencatatan yang aman.
- Dilarang menandai aplikasi production-ready sebelum seluruh quality gate lulus.

## 3. Kewajiban implementasi

- Gunakan PDO prepared statements dengan parameter terikat.
- Gunakan database transaction untuk operasi kritis.
- Gunakan row locking untuk submit attempt dan pemrosesan pembayaran.
- Gunakan UUID atau ULID sebagai public identifier; ID internal tidak diekspos.
- Gunakan integer dalam unit uang terkecil atau `DECIMAL` berpresisi eksplisit.
- Gunakan UTC di database dan waktu server sebagai otoritas.
- Gunakan timezone pengguna hanya pada saat ditampilkan.
- Gunakan audit log pada operasi penting, tanpa secret atau jawaban sensitif.
- Gunakan pagination dengan batas maksimum pada koleksi.
- Gunakan authentication dan authorization pada setiap endpoint, termasuk
  kepemilikan resource dan role/scope; endpoint publik harus ditandai eksplisit.
- Gunakan server-side validation; validasi klien hanya untuk pengalaman pengguna.
- Gunakan idempotency untuk autosave dan webhook.
- Gunakan versioning/immutable snapshot untuk soal yang telah dipublikasikan.

## 4. Definition of Ready (DoR)

Pekerjaan boleh dimulai hanya bila:

1. kebutuhan dan manfaat pengguna merujuk ke PRD;
2. scope, non-scope, acceptance criteria, dan contoh kegagalan jelas;
3. dependensi, pemilik, risiko, data sensitif, dan kebutuhan audit diidentifikasi;
4. kontrak OpenAPI serta perubahan migration dirancang bila relevan;
5. aturan authorization, entitlement, scoring, timer, dan idempotency dinyatakan;
6. rencana unit, integration, security, dan observability test tersedia;
7. desain/ADR telah disetujui bila mengubah arsitektur; dan
8. tidak ada pertanyaan produk atau keamanan berisiko tinggi yang belum terjawab.

## 5. Definition of Done (DoD)

Pekerjaan selesai bila acceptance criteria terpenuhi, code review disetujui,
OpenAPI/migration/dokumentasi sinkron, test baru relevan tersedia, dan seluruh
quality gate lulus. Authorization negatif, validasi batas, rollback, audit log,
observability, aksesibilitas UI, serta kompatibilitas mundur harus diuji sesuai
risiko. Tidak boleh ada secret, TODO tanpa issue, atau error tersembunyi. Perubahan
yang dapat dioperasikan wajib memiliki runbook/rollback dan telah berjalan di
staging sebelum produksi.

## 6. Branching, commit, dan pull request

### Branching rules

- Lindungi `main`; dilarang push langsung dan force-push.
- Buat branch pendek dari `main`: `feature/<issue>-<slug>`,
  `fix/<issue>-<slug>`, `docs/<issue>-<slug>`, atau `chore/<issue>-<slug>`.
- Satu branch untuk satu tujuan. Rebase/merge `main` dan selesaikan konflik sebelum
  review akhir; hapus branch sesudah merge.
- Hotfix mengikuti review dan quality gate yang sama; percepatan harus dicatat.

### Commit rules

- Commit harus kecil, koheren, dapat ditinjau, dan tidak mencampur refactor dengan
  perubahan perilaku tanpa alasan.
- Gunakan Conventional Commits (`feat:`, `fix:`, `docs:`, `test:`, `refactor:`,
  `chore:`), kalimat imperatif, dan referensi issue bila ada.
- Dilarang commit artefak build, kredensial, dump produksi, atau perubahan format
  massal yang tidak relevan. Commit harus lulus pemeriksaan lokal yang relevan.

### Pull request rules

- PR menjelaskan masalah, solusi, scope/non-scope, risiko, perubahan schema/API,
  keamanan, bukti test, screenshot UI, deployment, dan rollback.
- PR harus kecil dan memiliki tautan requirement/issue serta checklist DoD.
- CI wajib hijau, branch mutakhir, conversation selesai, dan minimal satu reviewer
  berwenang menyetujui; pemilik kode/security wajib meninjau area sensitif.
- Pembuat PR tidak boleh menjadi satu-satunya approver. Perubahan setelah approval
  yang material membatalkan approval sebelumnya.

### Code review rules

Reviewer memeriksa kebenaran domain, keterbacaan, test, kontrak, migrasi,
authorization, kebocoran data, injection, race condition, idempotency, logging,
kinerja query, dan rollback. Komentar dibedakan menjadi blocking dan saran.
Approval berarti reviewer memahami risiko, bukan sekadar CI lulus.

## 7. Dokumentasi dan perubahan data

### Documentation rules

- Perbarui dokumentasi dalam PR yang sama dengan perilaku yang berubah.
- Gunakan Bahasa Indonesia yang jelas; nama teknis/kontrak boleh berbahasa Inggris.
- Diagram Mermaid harus dapat dirender. Contoh request tidak boleh memuat secret
  atau data pribadi nyata.
- Keputusan lintas-domain dibuat sebagai ADR; perubahan status proyek dicatat di
  `14-project-memory.md`; OpenAPI diberi contoh sukses dan error.
- Dokumentasi usang harus diperbaiki atau diberi status/deprecation owner dan batas
  waktu, bukan dibiarkan ambigu.

### Migration rules

- Migration bersifat append-only setelah dibagikan; jangan mengedit migration yang
  telah diterapkan. Nama/version harus unik, deterministik, dan berurutan.
- Setiap migration direview untuk locking, indeks, foreign key, UTC, ukuran data,
  kompatibilitas aplikasi lama, dan strategi rollback/forward-fix.
- Perubahan destruktif memakai pola expand–migrate–contract, backup terverifikasi,
  dan dry-run pada data menyerupai produksi. Backfill harus resumable, terukur,
  dibatasi batch, serta tidak berada dalam request pengguna.
- Deployment aplikasi tidak boleh mengasumsikan migration selesai sebelum statusnya
  diverifikasi. Dilarang mengubah schema produksi secara manual.

### API versioning rules

- Endpoint publik berada di `/api/v1`; OpenAPI adalah kontrak kanonis.
- Perubahan backward-compatible boleh masuk versi aktif. Penghapusan/rename field,
  perubahan tipe/semantik/status code, atau pengetatan input yang memutus klien
  membutuhkan versi mayor baru.
- Field baru harus opsional atau memiliki default aman. Klien harus mengabaikan
  field respons yang tidak dikenal.
- Deprecation diumumkan dengan header/dokumentasi, telemetry penggunaan, tanggal
  sunset, dan panduan migrasi; versi lama dipertahankan selama kebijakan dukungan.

## 8. Error handling

- Gunakan bentuk error konsisten: `code`, `message`, `details`, dan `request_id`;
  pesan eksternal aman, stabil, dan tidak membocorkan stack trace, SQL, secret,
  jawaban benar, atau keberadaan resource yang tidak boleh diketahui.
- Petakan validation ke 422, unauthenticated 401, forbidden 403, not found 404,
  conflict/idempotency 409, rate limit 429, dan kegagalan tak terduga 500.
- Tangkap exception hanya jika dapat menambah konteks atau memulihkan; log
  terstruktur dengan correlation ID lalu rethrow/terjemahkan. Dilarang `catch`
  kosong atau melanjutkan transaksi setelah state tidak pasti.
- Rollback transaksi pada kegagalan. Timeout, retry dengan exponential backoff dan
  jitter hanya untuk operasi aman/idempotent; poison job masuk dead-letter queue.
- Log disanitasi dan tidak memuat password, token, jawaban, payment payload penuh,
  atau PII yang tidak perlu. Alert harus memiliki severity dan pemilik.

## 9. Quality gates

Sebelum merge: lint/format, static analysis, unit test, integration test, OpenAPI
validation, migration check, dependency/secret scan, dan build harus lulus. Untuk
alur kritis wajib ada test authorization positif/negatif, concurrency/idempotency,
dan end-to-end di staging. Target coverage ditetapkan per modul dan tidak boleh
turun tanpa persetujuan tertulis; coverage tidak menggantikan kualitas assertion.

Sebelum produksi: acceptance test, accessibility check, security review/threat
model, performance test terhadap SLO, backup/restore drill, monitoring/alert,
runbook, rollback, serta persetujuan product/engineering/operations harus lulus.
Temuan critical/high terbuka memblokir rilis. Pengecualian quality gate harus
memiliki pemilik risiko, mitigasi, persetujuan tertulis, dan tanggal kedaluwarsa;
aplikasi tetap tidak boleh disebut production-ready selama gate belum lulus.

## 10. Security escalation

Hentikan pekerjaan dan eskalasi segera untuk kebocoran secret/PII, bypass auth,
manipulasi skor/entitlement/pembayaran, SQL injection, remote code execution, atau
indikasi kompromi. Jangan menyalin bukti sensitif ke issue publik.

1. Laporkan melalui kanal keamanan privat kepada security lead dan incident
   commander; klasifikasikan severity dan catat waktu.
2. Untuk critical, mulai incident response segera: batasi akses, rotasi secret,
   pertahankan bukti, dan hentikan deploy terkait. Jangan merusak forensik.
3. Security menentukan containment, notifikasi hukum/pengguna, dan kriteria pulih.
4. Perbaikan memakai branch/PR privat bila perlu, mendapat review security, dan
   diuji terhadap regresi sebelum rilis.
5. Setelah insiden, buat postmortem tanpa menyalahkan individu dan tindak lanjut
   dengan pemilik serta tenggat. Hanya security yang boleh menurunkan severity atau
   menutup insiden.
