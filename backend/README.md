# Backend SINAESTA

Fondasi REST API PHP native ini memakai satu front controller, router internal,
PDO, opaque session token, dan middleware keamanan tanpa framework backend.

## Menjalankan lokal

```bash
composer install
cp .env.example .env
# buat database lalu terapkan migrations/001, 002, dan 003 secara berurutan
php -S 127.0.0.1:8080 -t public public/index.php
```

Document root harus diarahkan ke `backend/public`; direktori konfigurasi, source,
migration, dan storage tidak boleh dilayani web server. Token mentah hanya ada pada
respons login. Token verifikasi hanya muncul di non-production sebagai adapter mail
sementara; production wajib mengirimkannya melalui mail provider.

## Operasi dan keamanan

- Jalankan purge berkala untuk session/token/rate limit kedaluwarsa dan perbarui
  berkas `CRON_HEARTBEAT_FILE` paling lambat setiap 15 menit.
- Jalankan `php bin/auto-submit-attempts.php` setiap menit agar attempt kedaluwarsa
  dinilai dari snapshot server. Job aman dijalankan ulang.
- Set `AUTH_USE_COOKIE=true`, `AUTH_COOKIE_SECURE=true`, domain cookie yang tepat,
  serta origin CORS eksplisit di production. Klien cookie wajib mengirim pasangan
  cookie/header CSRF.
- IP disamarkan ke subnet sebelum di-hash; kebijakan retensi history dan audit
  harus ditetapkan operator sesuai kebijakan privasi organisasi.
- Endpoint readiness hanya mengembalikan status komponen dan tidak pernah nilai
  konfigurasi atau credential.

## Pemeriksaan

```bash
composer lint
composer test
```
