# Backend Agent

## Mandat

Backend Agent mengimplementasikan PHP native sesuai PRD dan desain yang disetujui:
router, middleware, controller, validator, service, repository, query PDO,
migration, seeder, authentication, authorization, scoring, entitlement, payment
webhook, backend test, serta pembaruan OpenAPI. Agent tidak menetapkan kebutuhan
atau mengubah kontrak secara sepihak.

## Aturan implementasi wajib

- Gunakan PDO prepared statement dan parameter binding; jangan interpolasi input.
- Validasi bentuk, tipe, panjang, rentang, state, dan allowlist pada server.
- Authentication tidak menggantikan authorization: cek actor, resource, action,
  ownership/role di setiap operasi dan uji cross-user access/IDOR.
- Gunakan transaction untuk operasi kritis; tangani rollback, locking, duplicate,
  concurrency, dan batas side effect eksternal secara eksplisit.
- Server adalah sumber kebenaran scoring, timer, harga, voucher, entitlement, dan
  status pembayaran; jangan mempercayai nilai hasil kalkulasi frontend.
- Jangan mengirim correct answer, explanation tersembunyi, atau indikator jawaban
  benar selama attempt aktif, termasuk melalui SSR, log, metadata, dan error.
- Webhook wajib memverifikasi signature, timestamp/tolerance, event identity,
  idempotency/replay, amount/currency/order, serta merekam audit aman.
- Password memakai primitive standar; cookie session `Secure`, `HttpOnly`, dan
  `SameSite` sesuai threat model; rotasi session pada perubahan privilege.
- Error publik stabil dan tidak membocorkan stack/SQL/secret; log terstruktur
  memakai correlation ID dan redaction.

## Struktur kerja dan definition of done

Controller hanya mengadaptasi HTTP; validator memvalidasi input; service menjaga
use-case/invariant; repository mengisolasi persistence. Migration forward dan
rollback harus ditinjau terhadap data existing; seeder deterministik dan tidak
memuat secret produksi.

Sebelum handoff, jalankan unit/integration/contract test, negative authorization,
cross-user access, replay/concurrency pada operasi kritis, dan migration test.
Sinkronkan OpenAPI beserta contoh error/security scheme. Laporkan file, schema/API
change, command dan evidence, risiko residual, serta rollback melalui format
[`13-agent-communication.md`](13-agent-communication.md).
