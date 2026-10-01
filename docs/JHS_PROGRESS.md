# Progres Pengembangan - JOSJIS Hospital System (JHS)

Status terakhir: **2026-10-01** | Fase: pra-rilis (fitur, pengujian, verifikasi
MySQL 8, hardening akun demo, dan repository GitHub selesai)

Dokumen ini adalah catatan progres kerja, hasil verifikasi, dan daftar item
yang masih tersisa. Detail teknis deployment ada di
[../DEPLOY.md](../DEPLOY.md).

---

## Ringkasan

Seluruh modul fungsional untuk lima peran sudah dibangun dan terotomasi
pengujiannya. Fokus terakhir adalah mencari cacat runtime yang ditemukan lewat
pengujian, perbaikan format kode, pemaketan deployment, verifikasi aplikasi
secara nyata di atas MySQL, dan menutup lubang akun demo sebelum sistem
dideploy.

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
| Pengujian otomatis | Selesai - 62 test / 338 assertion (MySQL), 58 test / 328 assertion (SQLite) |
| Kualitas format kode (Pint) | Selesai - `pint --test` lulus |
| Pemaketan deployment | Selesai - Dockerfile, compose, Nginx, DEPLOY.md |
| Verifikasi di MySQL 8 | **Selesai** - 62 test / 338 assertion |
| Uji konkurensi nomor dokumen | **Selesai** - 6 proses paralel, nol bentrok |
| Smoke test aplikasi di MySQL | **Selesai** - 5 peran, penomoran nyata via HTTP |
| Hardening akun demo | **Selesai** - default non-demo, admin awal, `jhs:demo-off` |
| Repository GitHub | **Selesai** - 3 commit di `main`, `.env` tidak ikut |
| Uji build image Docker | **Belum** - Docker tidak terpasang |

---

## Hasil Verifikasi Terakhir

| Pemeriksaan | Perintah | Hasil |
| --- | --- | --- |
| Pengujian | `php artisan test` | 58 passed, 4 skipped, 328 assertion |
| Pengujian MySQL 8 | `php artisan test -c phpunit.mysql.xml` | 62 passed, 338 assertion |
| Konkurensi nomor dokumen | `php artisan test -c phpunit.mysql.xml --filter=Concurrency` | 4 passed (6 proses paralel per skenario) |
| Smoke test HTTP di MySQL | login 5 peran + buat rekam medis & registrasi | 0 kegagalan |
| Format kode | `vendor/bin/pint --test` | passed (0 file) |
| Sintaks PHP | `php -l` (app/database/tests/routes/config) | 0 error |
| Cache view | `php artisan view:cache` | berhasil |
| Jumlah route | `php artisan route:list --json` | 143 |
| Migrasi + seeder produksi | `JHS_DEMO_CREDENTIALS=false migrate:fresh --seed` | 1 admin, nol data klinis |
| Migrasi + seeder pengembangan | `php artisan migrate:fresh --seed --force` | berhasil |
| `composer validate` | `composer validate` | valid |
| Git push | `git push origin main` | berhasil, sinkron dengan `origin/main` |
| Docker build | `docker compose up -d --build` | **tidak dijalankan** - Docker tidak terpasang |

### Smoke test aplikasi di atas MySQL

Feature test memakai transaksi yang dikontrol; smoke test ini memakai request
HTTP sungguhan terhadap server pengembangan (`php -S`) dengan basis data MySQL
`jhs_db`, supaya alur nyata diuji juga.

| Yang diuji | Hasil |
| --- | --- |
| Landing page sebagai tamu | HTTP 200 |
| Login admin, resepsionis, dokter, apoteker, pasien | 5/5 masuk ke dashboard masing-masing |
| Halaman kunci tiap peran | 5/5 HTTP 200 tanpa halaman error |
| Rekam medis oleh dokter | counter `RM` 61 → 62, nomor `RM-202610-0062`, antrean `COMPLETED` |
| Registrasi pasien oleh resepsionis | counter `P` 6 → 7, nomor `P-202610-0007` |
| NIK duplikat saat registrasi | ditolak dengan pesan yang benar |
| Konsistensi counter akhir | P 7/7, RM 62/62, RS 60/60 |
| Nomor dokumen duplikat | 0 pada `patients`, `medical_records`, dan `prescriptions` |

Catatan: smoke test meninggalkan dua baris tambahan pada `jhs_db` (pasien
"Uji Otomatis Smoke" dan rekam medis `RM-202610-0062`). Baris itu sengaja
tidak dihapus agar relasi dan audit log tetap utuh. Untuk kembali ke data
seed yang bersih: `php artisan migrate:fresh --seed`.

---

## Repository GitHub

Repository publik di `https://github.com/Valzien/Josjis-Hospital-System.git`,
branch `main`.

| Commit | Isi |
| --- | --- |
| `a966b2e` | Initial commit: JOSJIS Hospital System v1.0 (295 berkas) |
| `22aa531` | docs: catat `last_value` sebagai reserved word MySQL |
| `08cfd8d` | security: akun demo tidak lagi bocor ke produksi |

Audit sebelum commit: `.env` masuk `.gitignore`, `APP_KEY` tidak ada di berkas
ter-track, `database/database.sqlite` diabaikan, dan `storage/routes.json`
ditambahkan ke `.gitignore`. `.env.example` hanya memuat nilai kosong untuk
`JHS_ADMIN_PASSWORD`, tidak ada sandi contoh.

---

## Inventaris Kode

| Komponen | Jumlah |
| --- | --- |
| Model Eloquent | 15 |
| Enum domain | 8 |
| Controller | 41 |
| Form request | 17 |
| Middleware | 2 (`EnsureUserHasRole`, `EnsureQueueOwnership`) |
| Service | 6 |
| Migrasi | 17 |
| Factory | 13 |
| Blade view | 93 |
| Artisan command | 1 (`jhs:demo-off`) |
| Seeder | 4 (`DatabaseSeeder`, `SettingSeeder`, `InitialAdminSeeder`, `DemoSeeder`) |
| Feature test | 12 berkas |
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

### Akun demo bocor ke produksi

| Gejala | Perbaikan |
| --- | --- |
| `JHS_DEMO_CREDENTIALS` bernilai default `true` dan `DemoSeeder` tidak pernah memeriksanya, sehingga `migrate --seed` di server produksi selalu membuat lima akun berpassword `password` | Default dibalik menjadi `false`. `DatabaseSeeder` hanya menjalankan `DemoSeeder` saat flag aktif; selain itu memakai `InitialAdminSeeder` |
| Server produksi baru tidak punya akun administrator untuk masuk pertama kali | `InitialAdminSeeder` membuat satu admin dari `JHS_ADMIN_EMAIL`/`JHS_ADMIN_PASSWORD`, atau sandi acak yang ditampilkan sekali di konsol |
| Database yang sudah terlanjur ter-seed demo tidak punya cara mematikan akun tersebut dengan aman | Perintah `php artisan jhs:demo-off` (opsi `--list`, `--purge`) menonaktifkan akun demo dan mengacak ulang sandinya tanpa menghapus data klinis |
| Blok akun demo pada halaman login bisa muncul di produksi | Blade sudah mengikuti flag config, dan ada pengujian yang memastikan blok itu tersembunyi saat flag mati |

Pengaman regresi ada di `tests/Feature/DemoAccountGuardTest.php` (7 test):
seeding non-demo hanya menghasilkan satu admin, tidak pernah memakai sandi
`password`, tidak menghasilkan data klinis palsu, admin tidak terduplikasi,
blok login mengikuti flag, dan `jhs:demo-off` benar-benar menonaktifkan akun.

---

## Verifikasi MySQL 8

MySQL 8.4.3 (melalui Laragon) kini menjadi target pengujian. Suite dijalankan
dengan `php artisan test -c phpunit.mysql.xml`, memakai basis data `jhs_test`
terpisah dari basis data pengembangan `jhs_db`.

| Aspek | Hasil nyata |
| --- | --- |
| Seluruh suite | 62 passed / 338 assertion, `ONLY_FULL_GROUP_BY` dan `STRICT_TRANS_TABLES` aktif |
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
(RM 62/62, RS 60/60, P 7/7) tanpa lompatan nomor setelah smoke test HTTP.

### Catatan MySQL: `last_value` adalah reserved word

`last_value` adalah keyword MySQL (fungsi window). Query SQL mentah apa pun
yang menyebut kolom itu wajib memakai backtick, misalnya
`` SELECT `last_value` FROM document_counters ``. Tanpa backtick, MySQL 8
menolak query dengan galat sintaks 1064. Query di `NumberGenerator` sudah
menggunakan backtick, dan model Eloquent otomatis mengapit nama kolom, jadi
aman; hindari menulis SQL mentah tanpa backtick untuk tabel ini.

---

## Status Data Seed

Dihasilkan oleh `php artisan migrate:fresh --seed` dengan
`JHS_DEMO_CREDENTIALS=true`. Angka di bawah adalah kondisi `jhs_db` setelah
smoke test HTTP, jadi `patients` dan `medical_records` masing-masing satu lebih
banyak daripada seed murni.

| Tabel | Jumlah |
| --- | --- |
| users | 11 |
| patients | 7 (6 seed + 1 smoke test) |
| doctors | 4 |
| pharmacists | 1 |
| receptionists | 1 |
| doctor_schedules | 24 |
| medicines | 16 |
| queues | 66 |
| medical_records | 62 (61 seed + 1 smoke test) |
| prescriptions | 60 |
| prescription_details | 180 |
| medicine_transactions | 108 |
| settings | 12 |

Dengan `JHS_DEMO_CREDENTIALS=false` (produksi), hasil `migrate:fresh --seed`
justru hanya `settings` 12 baris dan satu `users`: nol pasien, antrean, dokter,
dan obat. Master data diisi lewat UI setelah login sebagai administrator.

### Pemeriksaan Integritas
| Pemeriksaan | Hasil |
| --- | --- |
| Stok negatif | 0 (aman) |
| Rek medis tanpa antrean | 0 (aman) |
| Total nilai resep | Rp 10.962.700 (konsisten) |
| Nomor dokumen duplikat | 0 pada pasien, rekam medis, dan resep |
| Konsistensi `document_counters` | P 7/7, RM 62/62, RS 60/60 |
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
| `tests/Feature/DemoAccountGuardTest.php` | 7 test pengaman agar akun demo tidak bocor ke produksi |
| `app/Console/Commands/DisableDemoAccountsCommand.php` | `php artisan jhs:demo-off` untuk mematikan akun demo di database yang sudah ter-seed |
| `database/seeders/InitialAdminSeeder.php` | Membuat satu administrator awal saat akun demo dinonaktifkan |
| `app/Models/DocumentCounter.php` | Model counter dokumen dengan scope `forPeriod()` |
| `database/migrations/2026_01_01_000014_create_document_counters_table.php` | Tabel counter unik per `scope` + `period` untuk penomoran atomik |
| `.env.example` | Ditulis ulang: MySQL 8, `APP_LOCALE=id`, seluruh key `JHS_*`, flag akun demo non-demo sebagai default |
| `public/images/favicon.svg` | Dirujuk kedua layout; sebelumnya hilang sehingga 404 |
| `DEPLOY.md` | Persyaratan, setup lokal, Docker, Nginx, checklist rilis, akun demo |
| `README.md` | Ditulis ulang; sebelumnya template stock Laravel |
| `docs/JHS_PROGRESS.md` | Dokumen ini: progres, hasil verifikasi, dan sisa pekerjaan |

---

## Sisa Pekerjaan

### Wajib sebelum produksi
- [ ] Jalankan `docker compose up -d --build` pada mesin dengan Docker; verifikasi image benar-benar dibangun.
- [ ] Pasang HTTPS dan sertifikat TLS.
- [ ] Siapkan backup database terjadwal (`jhs_db` belum punya jadwal dump).
- [ ] Uji kompatibilitas MySQL **8.0**. Seluruh verifikasi memakai MySQL 8.4.3
      lewat Laragon, sedangkan `docker-compose.yml` pinning `mysql:8.0`. Selisih
      versi ini belum pernah diuji.

### Sudah selesai
- [x] Jalankan seluruh suite terhadap **MySQL 8** (`php artisan test -c phpunit.mysql.xml`) - 62 test / 338 assertion lulus.
- [x] Uji konkurensi nomor dokumen dengan enam proses paralel - nol bentrok, nol deadlock.
- [x] Amankan akun demo (selesai 2026-10-01): default `JHS_DEMO_CREDENTIALS=false`, `InitialAdminSeeder` untuk admin awal, perintah `jhs:demo-off`, 7 test pengaman.
- [x] Arahkan aplikasi ke MySQL (selesai 2026-10-01): `.env` lokal memakai `jhs_db`, smoke test HTTP 5 peran dan penomoran dokumen nyata.
- [x] Dorong repository ke GitHub (selesai 2026-10-01): 3 commit di `main`, `.env` dan `database.sqlite` tidak ikut.

### Bertingkat prioritas rendah
- [x] Perbaiki dua string deskripsi obat di `database/seeders/DemoSeeder.php` yang masih campuran bahasa (selesai 2026-10-01):
  - `Obat untuk maag dan asam gastric.` -> `Obat maag untuk menurunkan asam gastrik.`
  - `Kortikosteroid untuk mediate inflamasi.` -> `Kortikosteroid untuk mereda inflamasi.`
- [ ] Bersihkan dua artefak smoke test di `jhs_db` (pasien "Uji Otomatis Smoke",
      rekam medis `RM-202610-0062`) atau jalankan `migrate:fresh --seed` agar data
      kembali persis seperti seed.
- [ ] Pertimbangkan menambahkan jadwal Minggu agar demo antrean selalu terisi, atau biarkan dan andalkan catatan di `DEPLOY.md`.
- [ ] Tambahkan `pint.json` bila ingin mengunci preset format kode.
- [ ] Simpan smoke test HTTP sebagai test permanen (skripnya ada di direktori
      temp, belum dipindahkan ke `tests/`), atau hapus setelah tidak diperlukan.

### Di luar cakupan saat ini
- Registrasi publik tidak membuat data pasien; pasien dibuat melalui resepsionis atau seeder.
- Tidak ada pengiriman notifikasi email atau SMS; papan antrean hanya pembaruan lewat polling.
- Tidak ada integrasi dengan sistem eksternal seperti BPJS.
