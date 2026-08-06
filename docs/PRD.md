# Product Requirements Document — Sinaesta

**Status:** Draf awal<br>
**Versi:** 0.1<br>
**Tanggal:** 6 Agustus 2026<br>
**Pemilik keputusan:** Belum ditentukan

## 1. Ringkasan

Sinaesta adalah nama kerja produk dalam repositori ini. Karena belum tersedia
brief bisnis, riset pengguna, maupun kode aplikasi, domain produk belum dapat
ditetapkan tanpa konfirmasi. Dokumen ini menjadi fondasi untuk menyepakati masalah,
pengguna, hasil yang diharapkan, dan batas MVP sebelum implementasi dimulai.

Tujuan fase dokumentasi adalah menghasilkan satu definisi produk yang dapat diuji,
memiliki kriteria penerimaan, dan cukup jelas untuk diterjemahkan menjadi desain
teknis. Tujuan fase ini **bukan** membangun backend bisnis.

## 2. Fakta, asumsi, dan pertanyaan terbuka

### Fakta yang tersedia

- Nama repositori adalah `sinaesta.new`.
- Repositori belum memiliki aplikasi atau kode bisnis.
- Dokumentasi dasar harus selesai sebelum backend bisnis dibuat.

### Asumsi sementara

- Produk akan memiliki antarmuka yang digunakan oleh pengguna akhir.
- Produk akan membutuhkan penyimpanan data dan layanan backend pada fase berikutnya.
- MVP perlu mengutamakan satu alur utama, bukan cakupan fitur yang luas.

Asumsi di atas bukan kebutuhan yang telah disetujui dan tidak boleh menjadi dasar
implementasi sebelum dikonfirmasi.

### Pertanyaan yang wajib dijawab pemilik produk

1. Masalah pengguna apa yang hendak diselesaikan dan untuk siapa?
2. Apa arti nama Sinaesta dan apa domain bisnis produknya?
3. Siapa persona primer, sekunder, dan administrator?
4. Apa satu alur pengguna yang menentukan keberhasilan MVP?
5. Data apa yang dikumpulkan, siapa pemiliknya, dan berapa lama disimpan?
6. Apakah ada data pribadi, transaksi, pembayaran, atau kewajiban regulasi?
7. Platform, wilayah, bahasa, dan target tanggal rilis apa yang dibutuhkan?
8. Metrik apa yang membuktikan MVP berhasil?

## 3. Tujuan dan ukuran keberhasilan

### Tujuan produk (draf)

- Memungkinkan persona primer menyelesaikan satu pekerjaan utama secara utuh.
- Membuat keberhasilan atau kegagalan alur utama dapat diukur.
- Menyediakan pengalaman yang aman, dapat dipahami, dan dapat dioperasikan.

### Metrik (harus diberi baseline dan target)

| Metrik | Definisi | Target |
| --- | --- | --- |
| Aktivasi | Pengguna yang menyelesaikan alur utama pertama kali | TBD |
| Penyelesaian alur | Persentase alur utama yang selesai tanpa error | TBD |
| Waktu penyelesaian | Median waktu untuk menyelesaikan pekerjaan utama | TBD |
| Retensi | Pengguna yang kembali dalam periode yang disepakati | TBD |
| Keandalan | Tingkat keberhasilan operasi kritis | TBD |

## 4. Persona dan alur utama

Persona primer, konteks penggunaan, kebutuhan aksesibilitas, dan alur utama masih
**TBD**. Sebelum persetujuan PRD, alur utama harus ditulis sebagai skenario:

> Sebagai **[persona]**, saya ingin **[pekerjaan]**, sehingga **[hasil bernilai]**.

Alur tersebut harus mencakup keadaan awal, langkah normal, kondisi kosong,
validasi, kegagalan, pemulihan, dan keadaan selesai.

## 5. Fitur MVP (draf untuk divalidasi)

Karena domain bisnis belum diketahui, daftar berikut mendefinisikan kapabilitas
minimum lintas-domain, bukan detail fitur final:

1. **Akses pengguna yang sesuai kebutuhan domain** — autentikasi hanya jika data
   atau alur memang membutuhkan identitas; akses tanpa akun dipilih bila memadai.
2. **Satu alur utama end-to-end** — membuat, melihat, memperbarui, atau
   menyelesaikan objek utama sesuai hasil discovery.
3. **Validasi dan umpan balik** — pesan yang jelas untuk sukses, input tidak valid,
   keadaan kosong, dan kegagalan yang dapat dipulihkan.
4. **Kontrol data dasar** — pengguna dapat melihat dan, jika relevan, memperbaiki
   atau menghapus data yang menjadi haknya.
5. **Operasional minimum** — pencatatan error, health check, dan audit untuk aksi
   sensitif, tanpa merekam rahasia atau data pribadi secara tidak perlu.
6. **Pengukuran hasil** — event minimum untuk mengukur aktivasi dan penyelesaian
   alur setelah definisi analitik dan persetujuan privasi tersedia.
7. **Aksesibilitas dan responsivitas dasar** — alur utama dapat digunakan dengan
   keyboard, label yang bermakna, serta layar kecil dan besar.

Setiap butir harus diganti dengan kebutuhan domain dan kriteria penerimaan yang
spesifik sebelum implementasi.

## 6. Di luar scope MVP

- Lebih dari satu alur bisnis utama.
- Aplikasi seluler native jika web responsif mencukupi.
- Integrasi pihak ketiga yang tidak wajib bagi alur utama.
- Personalisasi, rekomendasi cerdas, AI generatif, atau otomasi lanjutan.
- Paket berlangganan, pembayaran, marketplace, dan program referral kecuali
  merupakan inti domain yang telah dikonfirmasi.
- Pelaporan dan dashboard analitik tingkat lanjut.
- Multi-region, multi-tenant kompleks, white-label, dan kustomisasi enterprise.
- Optimasi skala sebelum target beban dan profil penggunaan diketahui.

## 7. Kebutuhan nonfungsional awal

- **Keamanan:** threat model, otorisasi per aksi, pengelolaan rahasia, validasi
  input, dan dependency scanning harus ditentukan dalam desain teknis.
- **Privasi:** minimisasi data, tujuan pemrosesan, retensi, ekspor, dan penghapusan
  harus disepakati sebelum data pengguna dikumpulkan.
- **Kinerja:** target berbasis persentil dan beban harus ditetapkan setelah alur
  utama serta target pengguna diketahui.
- **Keandalan:** definisikan SLO, strategi backup/restore, idempotensi, dan respons
  insiden sebelum produksi.
- **Aksesibilitas:** target awal WCAG 2.2 AA untuk antarmuka web, untuk dikonfirmasi.
- **Observabilitas:** log terstruktur, metrik, trace, dan alert harus menghindari
  data sensitif dan memiliki pemilik operasional.

## 8. Risiko utama

| Risiko | Dampak | Mitigasi sebelum implementasi |
| --- | --- | --- |
| Domain dan masalah pengguna belum diketahui | Membangun produk yang salah | Discovery dan persetujuan problem statement |
| Persona/alur utama belum dipilih | Scope melebar dan prioritas kabur | Pilih satu persona dan satu alur utama |
| Kriteria sukses belum terukur | MVP tidak dapat dievaluasi | Tetapkan baseline, target, dan event |
| Kebutuhan data/regulasi belum diketahui | Pelanggaran privasi atau redesign | Inventaris data dan review legal/security |
| Ketergantungan eksternal belum dipetakan | Jadwal dan reliabilitas berisiko | Spike, SLA, fallback, dan pemilik integrasi |
| Tidak ada fondasi kode/test/CI | Regresi dan proses rilis tidak konsisten | Putuskan stack lalu siapkan quality gates |
| Asumsi dianggap sebagai keputusan | Implementasi prematur | Decision log dan sign-off eksplisit |

## 9. Documentation gate

Backend bisnis **tidak boleh dimulai** sampai semua kondisi berikut terpenuhi:

- [ ] Problem statement dan proposisi nilai disetujui.
- [ ] Persona primer dan satu alur utama didokumentasikan.
- [ ] Kebutuhan MVP memiliki prioritas dan kriteria penerimaan yang dapat diuji.
- [ ] Non-scope disetujui pemilik produk.
- [ ] Metrik keberhasilan memiliki definisi dan target.
- [ ] Inventaris data, klasifikasi sensitivitas, retensi, dan kebutuhan regulasi ada.
- [ ] Risiko keamanan/privasi serta mitigasi awal telah direview.
- [ ] Dependensi eksternal, batas sistem, dan pemiliknya terdokumentasi.
- [ ] PRD mendapat persetujuan pemilik produk, design, dan engineering.
- [ ] Desain teknis/API dibuat **setelah** kebutuhan di atas stabil.

## 10. Status dokumentasi

| Dokumen/keputusan | Status | Catatan |
| --- | --- | --- |
| PRD | Draf awal | Struktur tersedia; detail domain masih TBD |
| Scope MVP/non-scope | Draf | Wajib divalidasi terhadap alur utama |
| Riset/persona | Belum tersedia | Blocking |
| User journey dan acceptance criteria | Belum tersedia | Blocking |
| Inventaris data dan privasi | Belum tersedia | Blocking |
| Threat model | Belum tersedia | Dibuat setelah alur/data jelas |
| Desain UX | Belum tersedia | Menunggu persona dan journey |
| Desain teknis/API | Belum dimulai | Sengaja ditahan oleh documentation gate |
| Runbook/operasional | Belum dimulai | Dibuat bersama desain teknis |

## 11. Kriteria penerimaan PRD

PRD dapat dinaikkan dari **Draf** menjadi **Disetujui** jika semua pertanyaan wajib
telah dijawab, seluruh TBD yang memengaruhi MVP telah ditutup, tiap fitur MVP
memiliki acceptance criteria, risiko memiliki pemilik, dan checklist documentation
gate telah ditandatangani oleh fungsi yang relevan.
