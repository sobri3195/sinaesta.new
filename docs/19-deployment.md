# Deployment dan Operasi SINAESTA

**Status:** Runbook baseline  
**Tujuan:** deployment repeatable, aman, observable, dan dapat dipulihkan

## 1. Model artifact dan prasyarat

CI menghasilkan artifact frontend/backend immutable dari commit yang telah lulus
gate. Dependency produksi dipasang dengan lockfile, development dependency dibuang,
asset frontend dibuild/minify, dan checksum/SBOM dicatat. Artifact yang diuji di
staging harus sama dengan yang dipromosikan ke produksi; jangan build ulang di
server produksi.

Prasyarat: versi PHP/extension sesuai `composer.json`, Node hanya bila runtime SSR
membutuhkannya, MySQL/MariaDB kompatibel migration, TLS valid, cron tersedia,
storage persisten/writable, document root mengarah ke `backend/public`, dan backup
terverifikasi. Kapasitas disk, memory, process, connection, DNS, serta akses provider
divalidasi sebelum change window.

## 2. Environment variables

`.env.example` mendokumentasikan nama dan nilai non-rahasia. Nilai produksi berasal
dari control panel atau secret manager, permission minimum, tidak disimpan di Git,
image, log, atau bundle frontend.

| Kelompok | Contoh | Aturan |
| --- | --- | --- |
| App | `APP_ENV`, `APP_URL`, `APP_DEBUG`, `APP_KEY` | `production`, URL HTTPS, debug false, key unik |
| DB | `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | akun least privilege; TLS bila lintas host |
| HTTP | `CORS_ALLOWED_ORIGINS`, `TRUSTED_PROXIES` | daftar origin/proxy eksplisit, bukan wildcard credentialed |
| Session | `SESSION_COOKIE_NAME`, `SESSION_SECURE`, `SESSION_SAME_SITE` | Secure, HttpOnly dari server, scope sempit |
| Provider | payment/mail credential dan webhook secret | rotatable, per-environment, jangan prefix publik |
| Ops | log level/channel, health access, telemetry endpoint | tidak mengandung payload sensitif |

Bootstrap melakukan fail-fast untuk variabel wajib dan kombinasi tidak aman. Hanya
variabel frontend yang sengaja publik boleh masuk build; secret tidak pernah punya
prefix/exposure publik. Rotasi secret memiliki prosedur overlap/revocation dan audit.

## 3. Target deployment

### 3.1 Shared hosting

1. Pastikan panel mendukung versi PHP/extension, rewrite, cron, HTTPS, SSH/deploy
   atomic, dan database yang diperlukan. Shared hosting yang tidak dapat memenuhi
   security/worker/rollback requirement tidak boleh menjadi produksi.
2. Build artifact di CI/lokal tepercaya, termasuk `vendor --no-dev` dan asset;
   jangan mengandalkan Composer/Node tersedia di host.
3. Upload ke direktori release bernomor di luar web root. Arahkan domain/subdomain
   hanya ke `backend/public`; frontend static/SSR dipublikasikan sesuai artifact.
4. Simpan `.env` dan storage persisten di luar release, lalu link dengan permission
   minimum. Nonaktifkan directory listing dan eksekusi PHP pada uploads.
5. Aktifkan `.htaccess`, migration terkontrol, cron, health, lalu smoke test. Switch
   symlink/release pointer secara atomik bila host mendukung; simpan release lama.

Jika hanya FTP tersedia, gunakan maintenance/read-only window dan manifest checksum;
risiko partial upload wajib diterima eksplisit. Database credential tidak boleh
berada dalam directory yang dapat diunduh.

### 3.2 VPS

- Buat user deploy non-root, SSH key, firewall hanya 80/443 (dan SSH terbatas),
  patch otomatis terkontrol, NTP, fail2ban/rate-limit sesuai threat model.
- Gunakan struktur `/var/www/sinaesta/releases/<id>`, `current` symlink, dan
  `/var/www/sinaesta/shared/{.env,storage}`. Ownership memisahkan deploy dan web user.
- Jalankan frontend SSR (bila ada) sebagai service manager terisolasi; backend via
  PHP-FPM Unix socket. Process restart graceful setelah symlink switch.
- Nginx menjadi reverse proxy/static server; MySQL idealnya private interface,
  credential least privilege, firewall dan backup terpisah.

### 3.3 Docker

- Image multi-stage, base image digest/versi terkunci, user non-root, read-only root
  filesystem bila memungkinkan, healthcheck, dan hanya runtime dependency.
- Pisahkan container web, PHP-FPM, frontend runtime, worker/cron, dan database.
  Secret diinjeksi saat runtime; source/secret tidak dibake ke layer.
- `docker-compose.yml` digunakan untuk development/single-host terkontrol. Produksi
  memakai volume persisten untuk DB/uploads, network internal, resource limit,
  restart policy, log driver/rotation, dan image registry tepercaya.
- Migration dijalankan sebagai one-off job sekali per release, bukan bersamaan oleh
  setiap replica. Deployment rolling hanya untuk perubahan backward-compatible.

## 4. PHP-FPM dan web server

PHP-FPM memakai pool khusus, user non-login, socket permission terbatas,
`display_errors=Off`, `log_errors=On`, ukuran upload/timeout sesuai use case,
`cgi.fix_pathinfo=0`, OPcache aktif, dan `pm.max_children` berdasarkan memory serta
kapasitas DB. Timeout web/PHP/provider harus berjenjang; request lambat dicatat.

Nginx wajib:

- redirect HTTP ke HTTPS dan terminasi TLS modern;
- `root .../backend/public`, `try_files $uri /index.php?$query_string`;
- meneruskan hanya `index.php` ke PHP-FPM, menolak file dot/env/config/source;
- menetapkan request/body limit, timeout, security headers, cache asset fingerprint;
- meneruskan scheme/client IP hanya dari trusted proxy dan menghasilkan access log
  dengan request ID tanpa query/header sensitif.

Apache memakai `AllowOverride FileInfo` minimum atau config vhost ekuivalen.
`backend/public/.htaccess` mengaktifkan `RewriteEngine`, meneruskan non-file/non-dir
ke `index.php`, menolak directory listing/dotfile, dan tidak boleh bergantung pada
konfigurasi rahasia. HTTPS/security header lebih baik di virtual host. Uji bahwa
`/.env`, `/composer.json`, `/src`, dan traversal mengembalikan 404/403.

## 5. MySQL

- Charset/collation `utf8mb4`, timezone koneksi UTC, strict SQL mode, InnoDB, dan
  version/parameter terdokumentasi.
- Pisahkan user aplikasi (DML minimum), migration (DDL saat deploy), backup, dan
  observability. Jangan berikan root/global privilege ke aplikasi.
- Tetapkan connection limit/pool terhadap kapasitas PHP-FPM, slow query log,
  threshold, index review, disk alert, dan retention binlog sesuai RPO.
- TLS digunakan untuk koneksi lintas host/network tidak tepercaya. At-rest
  encryption mengikuti klasifikasi data dan kemampuan platform.

## 6. CORS, TLS, dan perimeter

CORS memakai exact allowlist scheme/host/port. Jika cookie/credential digunakan,
`Access-Control-Allow-Origin` tidak boleh `*`, origin divalidasi, credentials hanya
untuk origin tepercaya, method/header minimal, dan preflight mempunyai cache aman.
CORS bukan authorization atau CSRF protection.

TLS 1.2+ (utamakan 1.3), sertifikat/chain valid, renewal otomatis dan expiry alert.
Aktifkan HSTS setelah seluruh subdomain siap, Secure/HttpOnly/SameSite cookie,
Content-Security-Policy bertahap, `X-Content-Type-Options`, frame policy, referrer
policy, dan pembatasan upload. WAF/CDN tidak menggantikan validasi aplikasi.

## 7. Cron dan background work

Cron menjalankan scheduler setiap menit dengan lock agar tidak overlap; command
bersifat idempoten, memiliki timeout, retry/backoff, dead-letter/failed-job record,
metrics, dan correlation/job ID. Pekerjaan minimum meliputi auto-submit attempt,
expiry subscription/entitlement, payment reconciliation, outbox delivery, cleanup
session/cache, agregasi, dan backup. Simpan UTC dan jangan menaruh secret di crontab
atau command line. Alert jika scheduler/queue heartbeat terlambat.

## 8. Backup dan restore

Tetapkan RPO/RTO bersama product owner. Minimal backup mencakup database konsisten,
uploads persisten, konfigurasi terenkripsi, dan manifest versi aplikasi/migration;
cache/log tidak dianggap sumber data. Gunakan jadwal full + incremental/binlog bila
perlu, enkripsi in transit/at rest, checksum, retention, akses terpisah, salinan
off-site/immutable, dan alert kegagalan.

Restore harus dilatih berkala pada environment terisolasi:

1. pilih recovery point dan verifikasi checksum/key;
2. siapkan versi aplikasi/schema yang kompatibel;
3. restore DB/uploads, replay log bila dibutuhkan, dan validasi row/file count;
4. jalankan integrity check, health, smoke, authorization, dan reconciliation;
5. dokumentasikan durasi aktual, data hilang, evidence, serta hapus data latihan.

Backup tanpa bukti restore bukan backup yang diterima.

## 9. Migration, release, dan rollback

### Pre-deployment

1. Catat artifact digest, release notes, owner, approval, change window, dan rollback
   trigger; verifikasi CI/security/UAT.
2. Periksa config, certificate, disk/capacity, dependency/provider, backup terbaru,
   replication/queue, serta kompatibilitas schema.
3. Latih migration pada salinan data representatif; ukur lock dan durasi. Gunakan
   expand–migrate/backfill–contract untuk perubahan destruktif.

### Deployment

1. Masuk maintenance/read-only hanya bila diperlukan; drain worker yang relevan.
2. Ambil backup/checkpoint dan catat migration version.
3. Deploy artifact baru yang backward-compatible, jalankan migration sekali dengan
   akun khusus, switch release secara atomik, reload PHP-FPM/frontend/worker.
4. Jalankan health dan smoke; pantau error, latency, DB, queue, serta business KPI.
5. Buka traffic bertahap dan tutup change setelah observation window lulus.

### Rollback

Trigger: health/smoke gagal, lonjakan error/latency, korupsi/incorrect payment atau
scoring, migration gagal, atau security regression. Hentikan traffic/worker yang
memperburuk keadaan, tunjuk incident commander, lalu rollback symlink/image dan
config. Schema hanya di-*down* bila terbukti aman; untuk migration destruktif,
gunakan forward fix atau restore ke checkpoint dengan keputusan kehilangan data
berdasarkan RPO. Reconcile request/payment yang terjadi selama window. Verifikasi
health/smoke/data, beritahu stakeholder, simpan evidence, dan lakukan RCA.

## 10. Logging, monitoring, dan health

- **Application/error log:** JSON terstruktur dengan UTC timestamp, level, service,
  release, request/correlation ID, stable error code; redaksi token, cookie,
  password, PII, payment payload, dan answer key.
- **Access log:** method, normalized route, status, bytes, duration, upstream,
  request ID; jangan log query sensitif atau authorization header.
- **Audit log:** terpisah/append-only untuk aksi keamanan/bisnis penting dengan
  retention dan akses terkontrol.
- **Log rotation:** size/time based, compression, retention sesuai kebijakan,
  filesystem quota, central shipping, serta alarm ingestion/disk; rotasi diuji agar
  worker tidak terus menulis ke file lama.

Monitor availability, p50/p95/p99 latency, 4xx/5xx, saturation CPU/memory/disk,
PHP-FPM queue, DB connections/slow query/replication, cron/queue lag/failures,
backup/restore status, TLS expiry, payment webhook/reconciliation, autosave/submit,
dan anomaly scoring. Alert harus actionable dengan severity, threshold, window,
runbook, owner, deduplication, dan escalation; dashboard tanpa alert bukan kontrol.

Sediakan:

- `GET /health/live`: proses hidup; cepat, tanpa dependency/detail sensitif.
- `GET /health/ready`: dependency kritis (DB/config/queue sesuai kebutuhan); dapat
  dilindungi network/auth dan tidak membocorkan credential/topologi.

Health tidak melakukan perubahan data atau query mahal. Orchestrator/container
memakai liveness untuk restart dan readiness untuk traffic; dependency eksternal
non-kritis tidak selalu membuat liveness gagal.

## 11. Post-deployment verification

Verifikasi terhadap release ID yang benar:

- checksum/image digest, config non-rahasia, migration version, process/cron/worker;
- TLS, redirect, security header, CORS origin valid/tidak valid, dan endpoint sensitif
  tidak terekspos;
- liveness/readiness, homepage/API version, login sandbox, akses role/ownership;
- alur read dan satu write yang aman/idempoten, autosave-submit-result bila relevan,
  payment sandbox/reconciliation tanpa charge nyata;
- error/access/audit log mempunyai request ID dan redaksi; metrics/trace/alerts masuk;
- error rate, latency, DB/queue, disk, dan KPI bisnis stabil selama observation
  window; backup berikutnya terjadwal.

Jika satu pemeriksaan wajib gagal, release tidak dinyatakan selesai: tahan traffic,
perbaiki atau jalankan rollback. Simpan command, timestamp, actor, release, hasil,
dan link dashboard sebagai evidence; setelah itu perbarui release notes/status.
