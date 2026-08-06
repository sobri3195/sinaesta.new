# Struktur Repositori SINAESTA

**Status:** Proposed baseline  
**Berlaku untuk:** seluruh source code, test, dokumentasi, dan artefak operasi

## 1. Prinsip

Repositori menggunakan *monorepo* agar kontrak frontend/backend, migration, test
lintas layanan, dan konfigurasi deployment berubah secara atomik. Struktur folder
adalah bagian dari arsitektur: dependency backend mengarah dari HTTP dan
Infrastructure menuju Application/Domain, sedangkan aturan bisnis tidak boleh
bergantung pada transport, PDO, atau frontend.

```text
sinaesta/
├── frontend/
│   ├── app/
│   ├── public/
│   ├── tests/
│   ├── package.json
│   ├── vite.config.ts
│   └── tsconfig.json
│
├── backend/
│   ├── public/
│   │   ├── index.php
│   │   └── .htaccess
│   ├── src/
│   │   ├── Application/
│   │   ├── Domain/
│   │   ├── Infrastructure/
│   │   ├── Http/
│   │   ├── Support/
│   │   └── Bootstrap/
│   ├── routes/
│   │   └── api.php
│   ├── database/
│   │   ├── migrations/
│   │   ├── seeders/
│   │   └── schema/
│   ├── storage/
│   │   ├── logs/
│   │   ├── cache/
│   │   └── uploads/
│   ├── tests/
│   │   ├── Unit/
│   │   ├── Integration/
│   │   └── Security/
│   ├── config/
│   ├── composer.json
│   └── .env.example
│
├── docs/
├── infrastructure/
│   ├── nginx/
│   ├── apache/
│   ├── docker/
│   ├── scripts/
│   └── monitoring/
│
├── tests/
│   ├── e2e/
│   ├── contract/
│   ├── performance/
│   └── security/
│
├── .github/
│   └── workflows/
│
├── docker-compose.yml
├── Makefile
├── README.md
└── .gitignore
```

Folder runtime `backend/storage` dibuat saat deploy. Hanya `.gitkeep` yang boleh
masuk Git; log, cache, unggahan, hasil build, `.env`, credential, dan dump database
harus diabaikan. Unggahan pengguna tidak boleh disajikan sebagai executable.

## 2. Frontend

`frontend/app` mengikuti konvensi TanStack Start dan organisasi berbasis fitur:

```text
frontend/app/
├── routes/              # file route; tipis dan melakukan composition
├── features/            # auth, question-bank, attempt, payment, dan seterusnya
│   └── <feature>/
│       ├── api/
│       ├── components/
│       ├── hooks/
│       ├── schemas/
│       └── types/
├── components/          # komponen UI generik, tanpa aturan domain
├── lib/                 # API client, query client, config, observability
└── styles/
```

`public` hanya untuk aset statis publik. Test yang dekat dengan unit boleh berada
di samping source sebagai `*.test.ts(x)`; fixture dan setup bersama berada di
`frontend/tests`. Route mengorkestrasi loader/component, bukan menyimpan harga,
permission, scoring, atau aturan entitlement.

## 3. Backend

### 3.1 HTTP

```text
src/Http/
├── Controllers/
├── Middleware/
├── Requests/
├── Resources/
└── Routing/
```

- **Controllers** menerjemahkan request tervalidasi menjadi pemanggilan use case.
- **Middleware** menangani correlation ID, content negotiation, auth, CSRF, CORS,
  rate limit, dan security headers.
- **Requests** berisi pemetaan dan validasi input transport.
- **Resources** membentuk response allowlist tanpa query database.
- **Routing** berisi router dan definisi tipe route; `routes/api.php` hanya
  meregistrasikan route `/api/v1` dan middleware.

`backend/public/index.php` adalah satu-satunya front controller web. Document root
server wajib menunjuk `backend/public`, bukan root repositori.

### 3.2 Application

```text
src/Application/
├── Services/
├── Commands/
├── Queries/
└── DTO/
```

- **Services** mengorkestrasi use case, authorization, transaction, dan event.
- **Commands** menyatakan niat perubahan; **Queries** membaca tanpa side effect.
- **DTO** adalah input/output application layer yang immutable dan typed.

Application boleh bergantung pada kontrak Domain, tetapi tidak pada request PHP
global atau bentuk JSON. Transaction boundary berada di use case, bukan controller.

### 3.3 Domain

```text
src/Domain/
├── Identity/
├── QuestionBank/
├── Attempt/
├── Scoring/
├── Subscription/
└── Payment/
```

Setiap folder domain dapat memiliki `Entities`, `ValueObjects`, `Enums`, `Events`,
`Services`, `Policies`, dan interface `Repositories`. Domain lain ditambahkan
dengan pola yang sama setelah disetujui arsitektur. Domain tidak mengimpor kelas
dari `Http` atau `Infrastructure`; komunikasi lintas domain memakai public
application service, interface, atau event/outbox.

### 3.4 Infrastructure

```text
src/Infrastructure/
├── Database/
├── Repositories/
├── Mail/
├── Payment/
├── Logging/
└── Security/
```

Folder ini berisi adapter PDO, implementasi repository, provider eksternal,
mailer, logger, hashing, dan verifikasi signature. Provider SDK dibungkus adapter
agar tidak bocor ke Domain. `Bootstrap` merakit dependency/container dan lifecycle;
`Support` menampung primitive lintas lapisan yang benar-benar generik seperti
`Clock`, `Uuid`, pagination, serta exception dasar—bukan tempat membuang logic.

## 4. Database, konfigurasi, dan operasi

- `database/migrations`: migration maju yang bernomor/timestamp, immutable setelah
  dirilis, dengan strategi rollback terdokumentasi.
- `database/seeders`: data referensi atau development yang aman dan idempoten;
  bukan akun/secret produksi.
- `database/schema`: snapshot schema untuk inspeksi, bukan pengganti migration.
- `config`: file konfigurasi typed yang membaca environment pada bootstrap.
- `.env.example`: seluruh nama variabel, nilai contoh non-rahasia, dan komentar.
- `infrastructure`: konfigurasi yang dapat direview untuk web server, container,
  script deploy/backup, dan dashboard/alert monitoring.

## 5. Penempatan test

| Lokasi | Isi |
| --- | --- |
| `backend/tests/Unit` | unit Domain/Application tanpa jaringan/database |
| `backend/tests/Integration` | repository, database, dan API backend |
| `backend/tests/Security` | authorization, injection, session, webhook |
| `frontend/tests` | setup, fixture, component/integration frontend |
| `tests/contract` | OpenAPI dan kompatibilitas consumer/provider |
| `tests/e2e` | perjalanan pengguna lintas frontend/backend |
| `tests/performance` | load, stress, endurance, dan threshold |
| `tests/security` | pemindaian/serangan lintas sistem |

Fixture tidak boleh berisi data produksi. Test lintas aplikasi berada di `tests`,
sedangkan test yang hanya membutuhkan satu aplikasi berada dekat aplikasi itu.

## 6. Aturan dependency dan ownership

```text
Http ───────► Application ───────► Domain
Bootstrap ──► semua composition root
Infrastructure ────────────────► Domain contracts
Frontend ───► HTTP contract, tidak pernah database/backend internals
```

Setiap perubahan struktur perlu memperbarui dokumen ini dan, bila mengubah batas
arsitektur, ADR. Dilarang membuat `helpers.php` global, circular dependency,
duplikasi model API, atau mengakses tabel domain lain langsung. Nama namespace
PSR-4 mencerminkan path, satu kelas utama per file, dan kapitalisasi path harus
konsisten agar deployment Linux tidak gagal.

## 7. Definition of done struktur

Struktur dianggap diterapkan ketika folder dibuat oleh pekerjaan implementasi,
autoload Composer dan TypeScript alias resolve, document root aman, artefak runtime
diabaikan Git, lint/test dapat dijalankan dari `Makefile`, serta CI memverifikasi
dependency direction dan tidak adanya secret. Folder kosong tidak dibuat hanya
untuk menyerupai diagram; dibuat bersama artefak pertama dan README lokal bila
tanggung jawabnya belum jelas.
