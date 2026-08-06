# Standar Testing SINAESTA

**Status:** Normatif  
**Prinsip:** risiko menentukan kedalaman test; coverage adalah sinyal, bukan tujuan

## 1. Test pyramid

Urutan berikut menunjukkan fondasi termurah/tercepat menuju verifikasi sistem yang
lebih sedikit tetapi lebih realistis. Tim tidak boleh mengganti unit/domain test
dengan E2E dalam jumlah besar.

1. **Unit test** — fungsi/value object/validator kecil, deterministik, tanpa I/O.
2. **Domain test** — invariant, state machine, policy, money, timer, dan scoring.
3. **Repository integration test** — implementasi PDO terhadap MySQL kompatibel
   produksi, termasuk constraint, transaction, lock, pagination, dan mapping.
4. **API integration test** — routing sampai database dengan auth, validation,
   response envelope, status, header, dan failure path.
5. **Contract test** — OpenAPI valid dan consumer/provider kompatibel; breaking
   change terdeteksi sebelum merge.
6. **Frontend component test** — render, interaction, accessibility dasar, form,
   loading/empty/error, dengan API boundary yang terkontrol.
7. **End-to-end test** — alur kritis nyata melalui browser dan API terdeploy.
8. **Security test** — authorization matrix, injection, XSS, CSRF, session,
   rate-limit, upload, webhook, dependency/secret scan.
9. **Performance test** — baseline, load, stress, spike, dan endurance terhadap
   SLO serta dataset representatif.
10. **Smoke test** — pemeriksaan cepat artifact hasil deploy, health, login, dan
    satu transaksi aman sebelum traffic penuh.

## 2. Target coverage

Coverage diukur pada kode yang dapat dieksekusi dan dilaporkan per modul serta diff.

| Area | Target minimum | Jalur kritis wajib |
| --- | ---: | --- |
| Scoring | **100% critical-path** | seluruh rubric, pembulatan, versi, invalid input, determinisme |
| Payment | **100% critical-path** | create, signature, amount/currency, duplicate/out-of-order webhook, settle/fail/refund |
| Authentication | **95%** | register/login/logout, hash, session rotation/expiry, brute-force, CSRF |
| Question workflow | **90%** | draft-review-publish/version/snapshot, role dan concurrency |
| Modul lain | **80%** | happy path, boundary, authorization, error, persistence penting |

Angka tanpa qualifier menggunakan line dan branch coverage; CI menampilkan keduanya
dan menolak penurunan di bawah threshold. Pengecualian generated code/migration
harus eksplisit. **Coverage tidak boleh menggantikan kualitas test**: assertion
lemah, test tanpa failure path, atau mengeksekusi baris tanpa membuktikan outcome
tidak memenuhi gate meskipun persentasenya tinggi.

## 3. Standar desain test

Setiap test harus:

- bernama berdasarkan kondisi dan outcome, memakai Arrange–Act–Assert/Given–When–Then;
- independen dari urutan, waktu mesin, locale, jaringan, dan data test lain;
- memakai fixed/injectable clock, seeded randomness, serta factory/fixture minimal;
- menguji output dan observable side effect, bukan detail private implementation;
- memiliki assertion bermakna dan pesan diagnosis yang cukup;
- membersihkan data atau menjalankan transaction/reset schema secara konsisten;
- tidak memakai sleep untuk sinkronisasi; tunggu kondisi dengan timeout terbatas;
- tidak memanggil provider produksi atau menggunakan PII/credential produksi.

Mock hanya untuk boundary lambat/tidak deterministik seperti provider eksternal,
clock, mail, dan queue. Jangan mock entity yang sedang diuji atau repository pada
repository integration test. Fake provider harus meniru signature, retry,
idempotency, error, serta out-of-order event yang relevan.

## 4. Matriks jalur kritis

### Authentication

Uji credential benar/salah, email normalization/duplikat, password policy dan
rehash, session fixation/rotation/revocation/expiry, cookie flags, logout, CSRF,
rate limit, enumeration resistance, dan isolasi user.

### Question dan attempt

Uji authorization tiap transisi, reviewer berbeda, version immutable, snapshot
tidak berubah setelah publish, answer key tidak bocor, server deadline, autosave
revision/idempotency, retry, concurrent save-vs-submit, auto-submit, restore, dan
akses attempt milik user lain.

### Scoring

Gunakan golden cases yang direview domain owner, property/boundary cases, urutan
acak, jawaban kosong/tidak valid, precision serta rounding, scoring version lama,
dan perhitungan ulang deterministik dari snapshot. Frontend input tidak boleh dapat
mengubah score otoritatif.

### Payment dan entitlement

Uji nominal/currency server, signature valid/tidak valid, replay, event duplikat dan
out-of-order, row locking, idempotency, timeout provider, reconciliation, status
terminal, voucher concurrency, invoice, grant tepat sekali, serta expiry berdasarkan
clock server. Tidak ada test yang benar-benar menagih instrumen pembayaran.

## 5. Contract, frontend, dan E2E

- OpenAPI dilint, schema request/response divalidasi terhadap server, dan breaking
  changes dibandingkan baseline rilis.
- Frontend menguji keyboard, accessible name, focus, validasi Zod/Form, cache Query,
  cancellation, retry aman, stale data, serta typed error.
- Axe/accessibility automation dipakai sebagai baseline, dilengkapi keyboard dan
  screen-reader review pada perjalanan kritis.
- E2E minimum: registrasi/login, latihan, quiz/tryout timer-autosave-submit-result,
  admin question workflow, serta purchase sandbox sampai entitlement.
- E2E menyimpan screenshot/trace/video hanya saat diperlukan dan meredaksi secret.
  Selector memakai role/label/test ID stabil, bukan class CSS rapuh.

## 6. Security dan performance

Security test mencakup unauthenticated/forbidden/object ownership, mass assignment,
SQL injection, XSS, CSRF/CORS, path traversal/upload, open redirect, session/token,
rate limiting, SSRF bila ada URL input, webhook authenticity, error disclosure,
dependency vulnerability, dan secret scanning. Temuan Critical/High memblokir rilis.

Performance scenario memiliki workload, dataset, concurrency, ramp, durasi,
environment, build, dan threshold terdokumentasi. Ukur latency p50/p95/p99,
throughput, error rate, CPU/memori, koneksi/query DB, dan queue lag. Jalur minimum:
login burst, daftar soal, autosave serentak, submit/scoring, hasil, webhook, dan admin
pagination. Test tidak dijalankan tanpa koordinasi terhadap produksi.

## 7. Pipeline dan gate

| Tahap | Pemicu | Gate minimum |
| --- | --- | --- |
| Lokal/PR cepat | setiap perubahan | lint, typecheck/static analysis, unit/domain, test terdampak |
| PR lengkap | sebelum merge | integration, API, contract, component, coverage, security scan, build |
| Staging | artifact kandidat | migration rehearsal, E2E kritis, smoke, security dinamis terjadwal |
| Pre-release | risiko/perubahan besar | performance/load, backup-restore, rollback rehearsal, UAT |
| Production | setelah deploy | health + smoke read-only/transaksi aman, monitoring dan log check |

Flaky test diperlakukan sebagai defect: karantina hanya dengan owner, issue, bukti,
masa berlaku, dan coverage kompensasi. Retry CI tidak boleh menyamarkan kegagalan.
Kegagalan test wajib menghasilkan exit code non-zero. Artifact evidence menyimpan
commit, environment, command, versi dependency, report, coverage, dan timestamp.

## 8. Test data dan environment

Schema test dibuat dari migration yang sama dengan produksi. Seed bersifat idempoten
dan dataset performance bervolume representatif tetapi sintetis. Secret sandbox
berada di secret manager CI, bukan repository. Timezone default UTC; test eksplisit
mencakup zona pengguna dan boundary tanggal/DST bila relevan. Container dan versi
MySQL/PHP/Node dikunci mendekati produksi.

## 9. Acceptance dan exit

Sebuah perubahan lolos testing ketika acceptance criteria memiliki test/evidence,
seluruh tingkat relevan lulus terhadap artifact yang sama, target coverage tercapai,
tidak ada test flaky yang tidak dikelola, defect ditriase, dan residual risk diterima
owner yang berwenang. Rilis tetap dapat ditolak walau coverage tercapai jika test
tidak menguji risiko nyata, observability/rollback belum siap, atau defect kritis
masih terbuka.
