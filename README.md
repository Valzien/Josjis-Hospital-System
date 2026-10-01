# JOSJIS Hospital System (JHS)

Sistem informasi rumah sakit terpadu untuk layanan pasien yang cepat, akurat,
dan dapat diaudit. Dibangun dengan Laravel 13 dan Bootstrap 5, mendukung lima
peran pengguna: admin, resepsionis, dokter, apoteker, dan pasien.

---

## Fitur Utama

| Modul | Kemampuan |
| --- | --- |
| **Admin** | Dashboard analitik, manajemen pasien/dokter/apoteker/resepsionis, pengguna, jadwal, pengaturan, laporan, ekspor CSV, audit log |
| **Resepsionis** | Papan antrean real-time, registrasi pasien, pencarian dokter, pemanggilan antrean, pengambilan rekam medis, input triage |
| **Dokter** | Dashboard klinis, antrean pribadi, pemeriksaan & triage, rekam medis, resep obat, cetak rekam medis/resep, jadwal praktik |
| **Apoteker** | Verifikasi & proses resep, dispence obat, transaksi stok, daftar obat, laporan stok, nilai persediaan |
| **Pasien** | Dashboard, lengkapi profil, ambil antrean, riwayat pemeriksaan, resep, daftar dokter, jadwal dokter |
| **Publik** | Landing page informatif, halaman login dengan blok akun demo |

## Alur Bisnis Inti

**Antrean** — nomor harian berurutan per dokter, dibatasi jam praktik, dengan
status `WAITING → CALLED → IN_EXAMINATION → COMPLETED` (dapat `CANCELLED`).
Pasien bisa langsung masuk pemeriksaan tanpa dipanggil. Setiap transisi
mencatat audit log.

**Resep** — `PENDING → PROCESSING → READY → COMPLETED`. Stok hanya berkurang
saat resep `COMPLETED`; pembatalan mengembalikan stok. Dispense ganda
ditolak oleh state machine.

**Stok** — transaksi bertipe `IN` (+), `OUT` (-), dan `ADJUSTMENT` (selisih
opname, tanpa arah). Penyesuaian memakai form request tersendiri.

**Nomor dokumen** — pola `PREFIX-YYYYMM-0001` untuk rekam medis (`RM`), resep
(`RS`), dan nomor pasien (`P`). Dihasilkan di dalam transaksi database memakai
penghitung pada tabel `document_counters`, bukan "SELECT ... FOR UPDATE" dari
tabel dokumen. Alasannya, penguncian baris tidak berguna bila baris yang
dikunci belum ada: pendekatan lama tidak mengunci apa pun di awal periode dan
memicu deadlock ketika beberapa permintaan menabung nomor bersamaan.

## Teknologi

- PHP 8.3, Laravel 13, Bootstrap 5.3.8, Bootstrap Icons 1.13.1
- ApexCharts 3.54.1 untuk grafik dashboard
- MySQL 8 (produksi) atau SQLite (pengembangan)
- Dompdf untuk cetak PDF, fakerphp/faker untuk data demo
- Tanpa build step frontend — seluruh aset berada di `public/vendor/`

## Struktur

```
app/
  Enums/            8 enum domain (status, peran, hari, dsb.)
  Http/Controllers/ 41 controller lintas 5 peran
  Http/Requests/    17 form request untuk validasi
  Http/Middleware/  EnsureUserHasRole, EnsureQueueOwnership
Models/           15 model Eloquent
  Services/         NumberGenerator, QueueService, PrescriptionService,
                     MedicineService, ReportService
database/
  migrations/       17 migrasi
  seeders/          DatabaseSeeder, SettingSeeder, DemoSeeder
  factories/        13 factory
resources/views/    93 Blade view
  components/       14 komponen reusable
  {admin,reception,doctor,pharmacy,patient}/
tests/
  Feature/          10 feature test
  Unit/             EnumDomainRulesTest
public/
  css/jhs.css       token desain + gaya kustom
  js/jhs.js         modal, konfirmasi, chart, auto-refresh antrean
  images/favicon.svg
docker/             konfigurasi image produksi
```

## Menjalankan

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Buka `http://localhost:8000`. Panduan lengkap untuk MySQL, Docker, dan
deployment ada di **[DEPLOY.md](DEPLOY.md)**.

## Pengujian

```bash
php artisan test
```

51 test / 299 assertion pada SQLite (4 pengujian konkurensi dilewati).
Cakupan:

| Berkas | Fokus |
| --- | --- |
| `EnumDomainRulesTest` | state machine resep & antrean, arah stok, home route per role |
| `LandingPageTest` | landing page, redirect tamu, blok akun demo |
| `AdminPagesTest` | seluruh halaman admin + ekspor laporan |
| `ReceptionPagesTest` | papan antrean, registrasi, pemanggilan |
| `DoctorPagesTest` | dashboard, antrean, rekam medis, resep, cetak |
| `PharmacyPagesTest` | resep, obat, stok, transaksi |
| `PatientPagesTest` | dashboard, profil, antrean, riwayat, resep |
| `QueueWorkflowTest` | nomor antrean, kuota, jam layanan, audit log |
| `PrescriptionWorkflowTest` | alur status resep, pengurangan & pengembalian stok |
| `StockWorkflowTest` | stok masuk, keluar, penyesuaian |
| `NumberGeneratorConcurrencyTest` | penomoran dokumen di bawah enam proses paralel (MySQL saja) |

Format kode dijaga dengan Pint:

```bash
vendor/bin/pint --test
```

Suite juga dapat dijalankan terhadap MySQL 8 lewat konfigurasi terpisah.
Pengujian konkurensi penomoran hanya diuji di sini, karena SQLite mengabaikan
`lockForUpdate()`:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS jhs_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan test -c phpunit.mysql.xml
```

Hasil terakhir: 55 test / 309 assertion pada MySQL 8.4.3, dan 51 test / 299
assertion pada SQLite in-memory (4 pengujian konkurensi dilewati).

## Dokumentasi

| Berkas | Isi |
| --- | --- |
| [DEPLOY.md](DEPLOY.md) | Persyaratan, setup lokal, Docker, Nginx, checklist rilis |
| [docs/JHS_Website_Project_Brief.md](docs/JHS_Website_Project_Brief.md) | Brief & requirement proyek |
| [docs/JHS_PROGRESS.md](docs/JHS_PROGRESS.md) | Catatan progres, verifikasi, dan item tersisa |
