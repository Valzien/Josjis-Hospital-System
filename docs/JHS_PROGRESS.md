# Progres Pengembangan - JOSJIS Hospital System (JHS)

Status terakhir: **2026-10-01** | Fase: pra-rilis (fitur & pengujian selesai,
verifikasi MySQL 8 selesai)

Dokumen ini adalah catatan progres kerja, hasil verifikasi, dan daftar item
yang masih tersisa. Detail teknis deployment ada di
[../DEPLOY.md](../DEPLOY.md).

---

## Ringkasan

Seluruh modul fungsional untuk lima peran sudah dibangun dan terotomasi
pengujiannya. Fokus terakhir adalah mencari cacat runtime yang ditemukan lewat
pengujian, perbaikan format kode, dan pemaketan deployment.

| Area | Status |
| --- | --- |
| Landing page & autentikasi | Selesai |
| Modul Admin | Selesai |
| Modul Resepsionis | Selesai |
| Modul Dokter | Selesai |
| Modul Apoteker | Selesai |
| Modul Pasien | Selesai |
| Laporan & ekspor CSV | Selesai |
| Audit log | Selesai |
| Data demo (seeder) | Selesai |
| Pengujian otomatis | Selesai - 51 test / 299 assertion (SQLite) |
| Kualitas format kode (Pint) | Selesai - `pint --test` lulus |
| Pemaketan deployment | Selesai - Dockerfile, compose, Nginx, DEPLOY.md |
| Verifikasi di MySQL 8 | **Selesai** - 55 test / 309 assertion |
| Uji konkurensi nomor dokumen | **Selesai** - 6 proses paralel, nol bentrok |
| Uji build image Docker | **Belum** - Docker tidak terpasang |

---

## Hasil Verifikasi Terakhir

| Pemeriksaan | Perintah | Hasil |
| --- | --- | --- |
| Pengujian | `php artisan test` | 51 passed, 4 skipped, 299 assertion |
| Pengujian MySQL 8 | `php artisan test -c phpunit.mysql.xml` | 55 passed, 309 assertion |
| Konkurensi nomor dokumen | `php artisan test -c phpunit.mysql.xml --filter=Concurrency` | 4 passed (6 proses paralel per skenario) |
| Format kode | `vendor/bin/pint --test` | passed (0 file) |
| Sintaks PHP | `php -l` (app/database/tests/routes/config) | 0 error |
| Cache view | `php artisan view:cache` | berhasil |
| Jumlah route | `php artisan route:list --json` | 143 |
| Migrasi + seeder | `php artisan migrate:fresh --seed --force` | berhasil |
| `composer validate` | `composer validate` | valid |
| Docker build | `docker compose up -d --build` | **tidak dijalankan** - Docker tidak terpasang |

---

## Inventaris Kode

| Komponen | Jumlah |
| --- | --- |
| Model Eloquent | 15 |
| Enum domain | 8 |
| Controller | 41 |
| Form request | 17 |
| Middleware | 2 (`EnsureUserHasRole`, `EnsureQueueOwnership`) |
| Service | 5 |
| Migrasi | 17 |
| Factory | 13 |
| Blade view | 93 |
| Feature test | 10 berkas |
| Unit test | 1 berkas |

Otorisasi memakai middleware `role:` pada setiap route group; proyek tidak
memakai `Gate`/Policy terpisah.

---

## Cacat yang Ditemukan & Diperbaiki

### Runtime
| Gejala | Perbaikan |
| --- | --- |
| `ComponentSlot::hasSection()` tidak ada, merusak named slot | Slot `card` ditulis ulang memakai API komponen yang benar |
| View dashboard pasien memakai variabel `$activeQueue`, sedangkan controller mengirim `$queue` | Variabel diselaraskan |
| Tiga view jadwal mengindeks array dengan enum `Day` | Diubah ke akses berbasis `case` |
| Tiga view memakai `@Data` (tipe data XML) | Diubah menjadi `Data` |
| Tombol konfirmasi memakai `data-jhs-confirm`, tidak cocok dengan `jhs.js` | Diubah menjadi `data-confirm` |
| `ReportExportController` memanggil `value()` tanpa arrow | Ditambahkan `->` |
| `ReportService::chart()` memakai alias SQL tidak valid dan tidak membedakan jenis laporan | Dibuat report-aware |

### Logika & keamanan
| Gejala | Perbaikan |
| --- | --- |
| `NumberGenerator` mengirim class-string ke tipe yang tidak cocok, dan memanggil `withTrashed()` pada model tanpa `SoftDeletes` | Tipe diperbaiki; `withTrashed()` dipanggil hanya bila trait terdeteksi |
| Pasien dengan profil belum lengkap bisa mengambil antrean | Guard ditambahkan di `Patient\QueueController` |
| Dokter dapat membuka atau menuntaskan antrean dokter lain | `EnsureQueueOwnership` plus pemeriksaan kepemilikan pada `show`, `start`, `complete` |
| Resep bisa di-dispense dua kali | `PrescriptionService::dispense()` hanya menerima status `READY` |
| Penyesuaian stok tidak punya validasi tersendiri | Dibuat `AdjustStockRequest` |
| `AuditLog::properties` tidak di-cast sehingga audit log selalu kosong | Ditambahkan cast `array` |
| `DoctorSchedule` tidak punya scope per dokter | Ditambahkan scope `forDoctor()` |

### Data demo
| Gejala | Perbaikan |
| --- | --- |
| `DatabaseSeeder` tidak memanggil seeder apa pun | Sekarang memanggil `SettingSeeder` lalu `DemoSeeder` |
| Loop riwayat memakai indeks numerik, bukan iterasi koleksi | Diubah menjadi `foreach ($doctors->values() ...)` |
| String demo mengandung karakter asing dan campuran bahasa | Teks &#38;Data diperbaiki; sisanya masih dipoles (lihat Sisa Pekerjaan) |

---

## Verifikasi MySQL 8

MySQL 8.4.3 (melalui Laragon) kini menjadi target pengujian. Suite dijalankan
dengan `php artisan test -c phpunit.mysql.xml`, memakai basis data `jhs_test`
terpisah dari basis data pengembangan `jhs_db`.

| Aspek | Hasil nyata |
| --- | --- |
| Seluruh suite | 55 passed / 309 assertion, `ONLY_FULL_GROUP_BY` dan `STRICT_TRANS_TABLES` aktif |
| `DATE_FORMAT` | Teruji lewat admin dashboard, dashboard/transaksi apoteker, seluruh halaman laporan, dan ekspor CSV |
| `groupBy` | Semua lolos pada `ONLY_FULL_GROUP_BY` |
| `ABS` & `COALESCE(SUM(...))` | Lolos di dashboard apoteker, transaksi, dan laporan |
| `timestamp()->useCurrent()` | Berlaku di `jobs.failed_at`, didukung MySQL 8 |
| Stok negatif | 0. Selain itu dicegah skema: `medicines.stock` bertipe `int unsigned`, sehingga mustahil negatif |
| Rek medis tanpa antrean | 0 |
| Antrean tanpa `doctor_schedule_id` | 10 - **diharapkan**, kolom `nullable` dengan `nullOnDelete` |

### Cacat yang ditemukan oleh pengujian MySQL

Pengujian konkurensi dengan enam proses PHP terpisah membuktikan bahwa
penomoran dokumen lama **tidak aman** dan bisa menggigit produksi:

| Gejala | Perbaikan |
| --- | --- |
| `SELECT ... LIKE 'RM-202610-%' ... FOR UPDATE` tidak mengunci apa pun saat periode masih kosong, dan memicu deadlock 1213 bahkan saat periode sudah terisi | Penomoran memakai tabel `document_counters` dengan satu statement upsert atomik (`ON DUPLICATE KEY UPDATE last_value = last_value + 1`) |
| `patientNumber()` menutup transaksinya sendiri sebelum `Patient::create()` berjalan, sehingga lock hilang sebelum insert. Dua registrasi berbarengan menghasilkan nomor sama dan salah satunya gagal dengan `1062 Duplicate entry` | Pembuatan nomor dan penyimpanan pasien dibungkus satu transaksi di `Reception\PatientController` dan `Admin\PatientController` |
| `INSERT IGNORE` diikuti `SELECT ... FOR UPDATE` menyebabkan deadlock upgrade kunci (S-lock di-upgrade ke X-lock) | Dihindari dengan menggabungkan keduanya menjadi satu statement |

Percobaan ulang deadlock lewat argumen `$attempts` pada `DB::transaction` juga
ditambahkan di titik masuk yang menghasilkan nomor dokumen.

Bukti pengukuran: `document_counters` tetap sinkron persis dengan data
(RM 61/61, RS 60/60, P 6/6) tanpa lompatan nomor.

---

## Status Data Seed

Dihasilkan oleh `php artisan migrate:fresh --seed`:

| Tabel | Jumlah |
| --- | --- |
| users | 11 |
| patients | 6 |
| doctors | 4 |
| pharmacists | 1 |
| receptionists | 1 |
| doctor_schedules | 24 |
| medicines | 16 |
| queues | 66 |
| medical_records | 61 |
| prescriptions | 60 |
| prescription_details | 180 |
| medicine_transactions | 108 |
| settings | 12 |

### Pemeriksaan Integritas
| Pemeriksaan | Hasil |
| --- | --- |
| Stok negatif | 0 (aman) |
| Rek medis tanpa antrean | 0 (aman) |
| Total nilai resep | Rp 10.962.700 (konsisten) |
| Antrean tanpa `doctor_schedule_id` | 10 - **diharapkan**, kolom ini `nullable` dengan `nullOnDelete`; terjadi untuk hari ketika dokter tidak berjadwal |

Jadwal demo hanya Senin-Sabtu sesuai brief. Kalau seeder dijalankan pada hari
Minggu, papan antrean "hari ini" akan kosong. Ini perilaku yang disengaja dan
sudah didokumentasikan di `DEPLOY.md`.

---

## Yang Sudah Dipasang

| Berkas | Isi |
| --- | --- |
| `Dockerfile` | Build 2 tahap: `composer:2` lalu `php:8.3-fpm-alpine` plus Nginx via supervisor |
| `docker-compose.yml` | Layanan `app` dan `mysql:8.0` dengan healthcheck, volume bernama, flag migrasi/seed |
| `docker/entrypoint.sh` | Menyalin `.env`, membuat `APP_KEY` sebelum `config:cache`, migrasi, seed opsional, cache route dan view |
| `docker/nginx/default.conf` | Document root ke `public/`, blokir file tersembunyi, kompresi |
| `docker/php.ini` | Batas unggah, timezone Asia/Jakarta, opcache, cookie httponly |
| `docker/supervisord.conf` | Nginx dan PHP-FPM |
| `.dockerignore` | Mengecualikan `vendor`, `.env`, `tests`, cache |
| `phpunit.mysql.xml` | Konfigurasi PHPUnit untuk menjalankan suite terhadap MySQL 8 (`jhs_test`) tanpa mengubah `phpunit.xml` |
| `tests/Fixtures/concurrent_number_worker.php` | Worker proses terpisah untuk pengujian konkurensi penomoran |
| `.env.example` | Ditulis ulang: MySQL 8, `APP_LOCALE=id`, seluruh key `JHS_*` |
| `public/images/favicon.svg` | Dirujuk kedua layout; sebelumnya hilang sehingga 404 |
| `DEPLOY.md` | Persyaratan, setup lokal, Docker, Nginx, checklist rilis, akun demo |
| `README.md` | Ditulis ulang; sebelumnya template stock Laravel |
| `docs/JHS_PROGRESS.md` | Dokumen ini: progres, hasil verifikasi, dan sisa pekerjaan |

---

## Sisa Pekerjaan

### Wajib sebelum produksi
- [ ] Jalankan `docker compose up -d --build` pada mesin dengan Docker; verifikasi image benar-benar dibangun.
- [x] Jalankan seluruh suite terhadap **MySQL 8** (`php artisan test -c phpunit.mysql.xml`) - 55 test / 309 assertion lulus.
- [x] Uji konkurensi nomor dokumen dengan enam proses paralel - nol bentrok, nol deadlock.
- [ ] Set `JHS_DEMO_CREDENTIALS=false` dan hapus akun demo bila data demo tidak dipakai.
- [ ] Pasang HTTPS dan sertifikat TLS.
- [ ] Siapkan backup database terjadwal.

### Bertingkat prioritas rendah
- [x] Perbaiki dua string deskripsi obat di `database/seeders/DemoSeeder.php` yang masih campuran bahasa (selesai 2026-10-01):
  - `Obat untuk maag dan asam gastric.` -> `Obat maag untuk menurunkan asam gastrik.`
  - `Kortikosteroid untuk mediate inflamasi.` -> `Kortikosteroid untuk mereda inflamasi.`
- [ ] Pertimbangkan menambahkan jadwal Minggu agar demo antrean selalu terisi, atau biarkan dan andalkan catatan di `DEPLOY.md`.
- [ ] Tambahkan `pint.json` bila ingin mengunci preset format kode.

### Di luar cakupan saat ini
- Registrasi publik tidak membuat data pasien; pasien dibuat melalui resepsionis atau seeder.
- Tidak ada pengiriman notifikasi email atau SMS; papan antrean hanya pembaruan lewat polling.
- Tidak ada integrasi dengan sistem eksternal seperti BPJS.
