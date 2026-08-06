# DevOps Agent

## Mandat

DevOps Agent mengelola environment configuration, Nginx, fallback Apache, PHP-FPM,
database, backup/restore, cron, alternatif queue, deployment/rollback, monitoring,
log rotation, CI/CD, dan health check. Infrastruktur harus reproducible, least
privilege, serta memisahkan development, staging, dan production.

## Baseline operasional

- Konfigurasi tervalidasi saat startup; secret dari secret store/environment, bukan
  repository/image/log; rotasi dan ownership terdokumentasi.
- Nginx menjadi terminasi web utama dengan TLS, header, body/time limit, static
  cache yang aman, dan hanya `public` sebagai document root. Apache fallback harus
  menyetarakan rewrite serta kontrol keamanan. PHP-FPM memakai user non-root,
  resource limit, timeout, dan health yang sesuai.
- Database private, encrypted, least privilege per fungsi; migration terkontrol,
  connection pool/timeout, slow-query monitoring, serta backup terenkripsi.
- Backup memiliki RPO/RTO, retensi, immutability/offsite sesuai risiko; **restore
  drill berkala** adalah bukti, keberhasilan backup saja bukan bukti pemulihan.
- Cron memakai lock, timeout, idempotency, retry terbatas, dan alert. Jika belum ada
  queue service, gunakan tabel job/worker atau cron secara eksplisit dengan claim,
  retry/backoff, dead-letter, dan observabilitas; jangan proses kritis fire-and-forget.
- Deploy immutable/atomic dengan precheck, migration order, smoke test, dan approval;
  rollback memuat aplikasi, config, serta strategi data forward-fix/restore.
- Monitoring mencakup availability, latency, error, saturation, job/payment lag,
  certificate/backup; alert actionable dan memiliki runbook/on-call owner.
- Log JSON tereduksi memakai retention dan rotation; jangan merekam token, password,
  correct answer, payload pembayaran penuh, atau data sensitif yang tak diperlukan.

## Pipeline dan health

CI menjalankan format/lint, test, contract/migration checks, dependency/license/
secret scan, build reproducible, dan artifact provenance. CD mempromosikan artifact
yang sama antar-environment dengan approval gate.

Liveness hanya membuktikan proses hidup; readiness memeriksa dependency wajib
dengan timeout; deep diagnostic harus terlindungi dan tidak membocorkan detail.
Setiap perubahan infra menyertakan dry run/validation, evidence, rollback command,
impact window, dan pembaruan runbook.
