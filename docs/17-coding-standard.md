# Standar Coding SINAESTA

**Status:** Normatif  
**Tujuan:** kode aman, type-safe, mudah diuji, dan konsisten lintas aplikasi

Kata **WAJIB**, **DILARANG**, dan **SEBAIKNYA** menunjukkan tingkat kepatuhan.
Pengecualian harus memiliki alasan di pull request, owner, test kompensasi, dan
batas waktu penghapusan. Formatter, static analysis, lint, test, serta review
manusia menjadi quality gate sebelum merge.

## 1. Prinsip umum

- Perubahan kecil, kohesif, dapat ditinjau, dan terlacak ke requirement.
- Nama mengungkap maksud bisnis; komentar menjelaskan *mengapa*, bukan menyalin
  implementasi.
- Waktu, ID, random, filesystem, dan provider eksternal dibungkus agar testable.
- Semua input eksternal dianggap tidak tepercaya; validasi bentuk, authorization
  objek, dan invariant domain adalah langkah terpisah.
- Secret/PII/credential/answer key aktif tidak masuk source, exception publik,
  analytics, fixture, screenshot, atau log.
- API, schema, migration, dokumentasi, dan test diperbarui dalam perubahan yang
  sama. Backward compatibility dipertahankan atau diberi versi/migration plan.

## 2. Native PHP

### 2.1 Bahasa dan format

- Composer memakai **PSR-4 autoloading** dan source mengikuti namespace/path.
- Semua PHP mengikuti **PSR-12**, memakai `<?php` dan tanpa closing tag pada file
  PHP murni.
- Setiap file source memulai dengan `declare(strict_types=1);` setelah opening tag.
- Typed property, typed parameter, dan return type wajib. Gunakan value object,
  generic PHPDoc yang dapat dianalisis, atau union type; jangan melemahkan tipe.
- Class diberi `final` jika inheritance bukan extension point yang disengaja.
- Status dan state machine memakai backed `enum`, bukan magic string/angka.
- Dependency diberikan melalui constructor; entity tidak mengambil service dari
  container. Property dibuat `readonly` bila memang immutable.
- Satu kelas/enum/interface utama per file. Nama class `PascalCase`, method/property
  `camelCase`, konstanta `UPPER_SNAKE_CASE`.

```php
<?php

declare(strict_types=1);

namespace Sinaesta\Application\Attempt\DTO;

final readonly class SubmitAttemptInput
{
    public function __construct(
        public string $attemptId,
        public string $actorId,
        public string $idempotencyKey,
    ) {
    }
}
```

### 2.2 Pemisahan tanggung jawab

- **DTO** typed dan immutable membawa input ke application layer; request mentah
  tidak melewati controller.
- **Repository** adalah satu-satunya akses persistence. Interface berada di
  Domain/Application dan implementasi PDO berada di Infrastructure.
- **Service/use case** menyimpan orchestration dan business logic. Domain entity,
  value object, serta policy menjaga invariant.
- **Controller** hanya melakukan HTTP orchestration: principal/input tervalidasi,
  panggil use case, lalu format resource/response.
- Response transformer hanya memetakan data dengan allowlist; query database dan
  keputusan authorization di transformer dilarang.
- Validator sentral menghasilkan error terstruktur. Validasi transport tidak
  menggantikan invariant atau authorization server-side.
- Response formatter sentral menghasilkan envelope, status, content type,
  correlation ID, pagination, serta error code yang konsisten.
- Exception handler sentral memetakan exception dikenal ke status aman dan mencatat
  exception tak terduga menggunakan correlation ID.

### 2.3 Database dan transaksi

- PDO menggunakan native **prepared statement**, parameter binding, charset
  `utf8mb4`, error exception, dan fetch mode eksplisit.
- Nama tabel/kolom dinamis hanya dari allowlist; nilai pengguna tidak pernah
  digabung ke SQL. Query memilih kolom eksplisit dan memiliki batas/pagination.
- Transaction membungkus satu perubahan atomik dan outbox terkait. Gunakan
  `commit` setelah semua invariant terpenuhi dan `rollBack` pada kegagalan.
- Operasi concurrency-sensitive melakukan unique constraint/row lock, mengecek
  state kembali setelah lock, serta idempotency key. Network call tidak ditahan di
  dalam transaksi database; gunakan outbox/state machine dan reconciliation.
- Migration maju adalah sumber perubahan schema. Perubahan destruktif memakai pola
  expand–migrate–contract dan rencana rollback/restore.

### 2.4 Error dan keamanan

- Password hanya diproses dengan `password_hash`, `password_verify`, dan kebijakan
  rehash; tidak disimpan atau dilog sebagai plaintext.
- Secret dibaca dari environment/secret store dan divalidasi saat bootstrap.
- Production mengirim pesan generik dan stable error code, tidak stack trace,
  query, path internal, atau detail provider.
- Catch hanya digunakan bila dapat menambah context, memetakan exception, melakukan
  rollback/cleanup, atau recovery. Exception tidak boleh ditelan.
- Audit event penting menyimpan actor, action, resource, hasil, waktu, correlation
  ID, tanpa payload sensitif.

### 2.5 Larangan PHP

Dilarang:

1. Global database variable atau mutable global state.
2. SQL string concatenation dengan input/data dinamis.
3. Logic bisnis kompleks di controller.
4. Query database di response transformer.
5. Password plaintext atau reversible encryption untuk password.
6. Secret hard-coded.
7. `die()` atau `exit()` untuk error handling (front controller boleh berakhir
   alami setelah response dikirim).
8. Mengirim detail error produksi kepada pengguna.
9. Operator `@` untuk error suppression.
10. `catch` kosong atau catch yang hanya mengabaikan error.

## 3. TypeScript dan TanStack Start

### 3.1 Type safety dan organisasi

- `tsconfig` menjalankan `strict` dan pemeriksaan null/index yang disepakati tim.
- Ikuti file convention **TanStack Start** dan struktur route **TanStack Router**;
  route file tipis, lazy boundary jelas, parameter/search divalidasi.
- Organisasi feature-based: API, schema, hook, component, dan tipe spesifik fitur
  berdekatan. Shared hanya untuk kode yang benar-benar generik.
- Response sukses dan error API typed. Payload `unknown` divalidasi di boundary
  dengan Zod sebelum dianggap sebagai domain/UI type.
- Gunakan discriminated union untuk state async/error; tangani seluruh varian secara
  exhaustive. `any` hanya boleh dengan komentar alasan, scope minimum, dan issue
  penghapusan; umumnya gunakan `unknown`.

### 3.2 Data dan form

- **TanStack Query** mengelola server state, cache key terpusat, invalidation, dan
  retry yang aman. Jangan menduplikasi server state ke global client store.
- **TanStack Form + Zod** mengelola state dan validasi form. Validasi frontend
  memberi UX, tetapi backend tetap memvalidasi dan mengotorisasi.
- **TanStack Table** digunakan untuk tabel admin: column definition typed,
  pagination/sort/filter server-side untuk data besar, dan action berizin.
- Semua HTTP melewati central API client yang menangani base URL, credential,
  timeout/abort, content type, correlation ID, parsing envelope, serta typed error.
  Feature membungkus client tersebut dengan fungsi API yang bernama jelas.
- Harga, diskon, total pembayaran, scoring, waktu server, status payment,
  entitlement, dan permission final berasal dari backend. UI hanya menampilkan
  nilai otoritatif dan boleh memberi preview berlabel non-otoritatif.

```ts
export type ApiError = {
  code: string
  message: string
  requestId: string
  fieldErrors?: Record<string, readonly string[]>
}

export type ApiResult<T> =
  | { ok: true; data: T }
  | { ok: false; error: ApiError }
```

### 3.3 UI dan aksesibilitas

- Gunakan semantic HTML (`button`, `label`, heading berurutan, landmark) sebelum
  ARIA. Elemen interaktif dapat dicapai dan dioperasikan dengan keyboard.
- Fokus terlihat, modal mengelola focus/escape/return focus, error form terhubung
  ke field, status async diumumkan secukupnya, dan warna bukan satu-satunya sinyal.
- Loading, empty, offline, forbidden, not-found, validation, dan unexpected error
  mempunyai state eksplisit. Mutation penting mencegah submit ganda tanpa
  menyembunyikan hasil server.
- Content pengguna di-escape; penggunaan HTML mentah membutuhkan sanitizer,
  threat review, dan test XSS.

### 3.4 Larangan TypeScript/frontend

Dilarang:

1. `any` tanpa alasan terdokumentasi.
2. Pemanggilan `fetch` langsung yang tersebar di component/route.
3. Harga atau paket hard-coded di halaman.
4. Nilai/scoring/payment total final dihitung oleh frontend.
5. Permission hanya diperiksa frontend; backend wajib menegakkan authorization.
6. Token, cookie, secret, PII, atau payload sensitif ditulis ke console/log.
7. Secret dimasukkan ke bundle frontend; environment publik dianggap dapat dibaca
   siapa saja.

## 4. Review dan enforcement

Pull request wajib lulus formatter, lint, static analysis, unit/integration test
yang relevan, typecheck, build, secret scan, dan dependency audit sesuai risiko.
Reviewer memeriksa boundary layer, failure path, authorization objek, transaksi,
concurrency, accessibility, observability, dan backward compatibility—bukan hanya
format. `TODO` wajib memiliki issue/owner; suppression lint/static analysis harus
lokal, beralasan, dan diuji.

Definition of done: kode memenuhi standar ini, tidak menambah warning, test
membuktikan happy path dan failure path, dokumentasi/kontrak/migration sinkron,
serta tidak ada larangan di atas yang tersisa.
