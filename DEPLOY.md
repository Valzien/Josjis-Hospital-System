# DEPLOY — JOSJIS Hospital System (JHS)

Dokumen ini mencakup persyaratan, cara menjalankan secara lokal, deployment
menggunakan Docker, dan checklist sebelum rilis.

---

## 1. Persyaratan

| Komponen | Versi | Catatan |
| --- | --- | --- |
| PHP | 8.3+ | ekstensi: `pdo_mysql`, `mbstring`, `intl`, `gd`, `zip`, `bcmath`, `fileinfo`, `openssl` |
| Composer | 2.x | untuk memasang dependensi |
| MySQL | 8.0+ | InnoDB, utf8mb4 |
| Nginx | 1.24+ | opsional bila memakai Apache |
| Node.js | 20+ | **tidak diperlukan** — proyek tidak memakai Vite/npm |

Frontend memakai aset statis di `public/vendor/` (Bootstrap 5.3.8,
Bootstrap Icons 1.13.1, ApexCharts 3.54.1). Tidak ada proses build.

---

## 2. Menjalankan secara lokal

### 2.1 Dengan MySQL

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Sesuaikan `.env`:

```dotenv
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=josjis_hospital
DB_USERNAME=josjis
DB_PASSWORD=rahasia
```

Buat database lalu jalankan:

```bash
php artisan migrate --seed
php artisan serve
```

### 2.2 Dengan SQLite (tanpa MySQL)

```bash
composer install
cp .env.example .env
php artisan key:generate

# aktifkan blok SQLite dan nonaktifkan blok MySQL di .env
# DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/database/database.sqlite

touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

### 2.3 Akun demo

Nilai default `JHS_DEMO_CREDENTIALS` adalah `false` (aman). Selama bernilai
`true`, `DemoSeeder` berjalan dan halaman login menampilkan daftar akun demo.
Saat bernilai `false`, `DatabaseSeeder` **tidak** menjalankan `DemoSeeder`;
yang dibuat hanya pengaturan sistem dan satu administrator awal dari
`InitialAdminSeeder`.

Administrator awal memakai `JHS_ADMIN_EMAIL` dan `JHS_ADMIN_PASSWORD`. Bila
`JHS_ADMIN_PASSWORD` dikosongkan, seeder membuat sandi acak dan menampilkannya
satu kali di konsol.

```env
JHS_DEMO_CREDENTIALS=false
JHS_ADMIN_EMAIL=admin@josjis.test
JHS_ADMIN_PASSWORD=SandiKuat-JHS-2026!
```

Login pertama administrator, lalu buat akun resepsionis, dokter, dan apoteker
melalui menu **Admin → Pengguna**. Master data obat dan jadwal dokter diisi
melalui UI, bukan seeder.

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@josjis.test` | `password` |
| Resepsionis | `resepsionis@josjis.test` | `password` |
| Dokter | `dokter@josjis.test` | `password` |
| Apoteker | `apoteker@josjis.test` | `password` |
| Pasien | `pasien@josjis.test` | `password` |

Baris di atas hanya berlaku di mesin pengembangan dengan
`JHS_DEMO_CREDENTIALS=true`.

#### Mematikan akun demo pada database yang sudah terlanjur ter-seed

```bash
php artisan jhs:demo-off --list   # lihat dulu, tanpa mengubah apa pun
php artisan jhs:demo-off          # nonaktifkan + acak ulang sandi
```

Perintah ini tidak menghapus data klinis, hanya menonaktifkan lima akun demo.
Opsi `--purge` menghapus akunnya permanen; pakai hanya bila data klinis yang
terkait sudah tidak diperlukan.

> Data demo membuat jadwal Senin–Sabtu. Antrean "hari ini" hanya dibuat bila
> ada dokter yang berjadwal pada hari seed dijalankan. Jalankan
> `php artisan db:seed` pada hari kerja agar papan antrean terisi.

---

## 3. Deployment dengan Docker

### 3.1 swiftly prepare

```bash
cp .env.example .env
php artisan key:generate
```

Pastikan nilai berikut di `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jhs.example.com

DB_DATABASE=josjis_hospital
DB_USERNAME=josjis
DB_PASSWORD=ganti-dengan-sandi-kuat
DB_ROOT_PASSWORD=ganti-dengan-sandi-kuat

JHS_DEMO_CREDENTIALS=false
```

### 3.2 Build dan jalankan

```bash
docker compose up -d --build
docker compose logs -f app
```

Aplikasi tersedia di `http://localhost:8000`.

MySQL tidak dipetakan ke host secara default. Port `3307` di map ke `3306`
agar tidak bentrok dengan MySQL lokal. Ubah bila diperlukan lewat
`DB_FORWARD_PORT`.

### 3.3 Seed data demo di container (opsional)

```bash
JHS_RUN_SEEDERS=true docker compose up -d --build
```

### 3.4 Perintah Docker yang sering dipakai

```bash
docker compose ps
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec app php artisan config:clear
docker compose exec app php artisan test
docker compose down
docker compose down -v      # HAPUS volume database
```

### 3.5 Isi image

| Lapisan | Isi |
| --- | --- |
| `vendor` | `composer install --no-dev --optimize-autoloader` |
| `runtime` | `php:8.3-fpm-alpine` + ekstensi, Nginx, supervisor |

- `storage` dan `bootstrap/cache` dimount sebagai volume agar tidak hilang
  saat image dibangun ulang.
- Entrypoint (`docker/entrypoint.sh`) otomatis: menyalin `.env` bila belum ada,
  membuat `APP_KEY` bila kosong, menjalankan migrasi, dan menyimpan cache.

---

## 4. Deployment tanpa Docker (Nginx + PHP-FPM)

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Arahkan `document root` ke folder `public/`:

```nginx
server {
    listen 80;
    server_name jhs.example.com;
    root /var/www/jhs/public;
    index index.php;

    client_max_body_size 12M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\. { deny all; }
}
```

Direktori `storage` dan `bootstrap/cache` harus writable oleh pengguna web
server:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

Optimasi produksi:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Setelah mengubah `.env`, jalankan `php artisan config:clear`.

---

## 5. Checklist sebelum rilis

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` memakai domain sebenarnya (dipakai untuk tautan cetak/PDF)
- [ ] `APP_KEY` sudah di-generate dan **tidak** ada di dalam repository
- [ ] `JHS_DEMO_CREDENTIALS=false` (sudah menjadi nilai default)
- [ ] `JHS_ADMIN_PASSWORD` diisi sandi kuat, bukan `password`
- [ ] `php artisan jhs:demo-off --list` menyisakan nol akun demo aktif
- [ ] Akun resepsionis, dokter, dan apoteker dibuat lewat UI, bukan seeder
- [ ] `php artisan migrate --force` sudah dijalankan
- [ ] Document root mengarah ke `public/`
- [ ] HTTPS aktif
- [ ] Backup database terjadwal
- [ ] `php artisan test` lulus
- [ ] `php artisan test -c phpunit.mysql.xml` lulus terhadap MySQL 8

---

## 6. Pengujian

```bash
php artisan test
```

Suite ini mencakup:

| Berkas | Cakupan |
| --- | --- |
| `tests/Feature/LandingPageTest.php` | landing page, redirect tamu |
| `tests/Feature/AdminPagesTest.php` | seluruh halaman admin + ekspor laporan |
| `tests/Feature/ReceptionPagesTest.php` | papan antrean, registrasi, pemanggilan |
| `tests/Feature/DoctorPagesTest.php` | dashboard, antrean, rekam medis, resep, cetak |
| `tests/Feature/PharmacyPagesTest.php` | resep, obat, stok, transaksi |
| `tests/Feature/PatientPagesTest.php` | dashboard, profil, antrean, riwayat, resep |
| `tests/Feature/QueueWorkflowTest.php` | nomor antrean, kuota, jam layanan, audit log |
| `tests/Feature/PrescriptionWorkflowTest.php` | alur status resep dan pengurangan stok |
| `tests/Feature/StockWorkflowTest.php` | stok masuk/keluar/penyesuaian |
| `tests/Feature/NumberGeneratorConcurrencyTest.php` | penomoran dokumen di bawah enam proses paralel (MySQL saja) |

Pengujian memakai database in-memory (`.env.testing` / `phpunit.xml`),
sehingga tidak menyentuh database pengembangan.

### 6.1 Menjalankan suite terhadap MySQL 8

Pengujian konkurensi penomoran dokumen hanya bermakna pada MySQL, karena SQLite
mengabaikan `lockForUpdate()`. Untuk itu tersedia konfigurasi terpisah; basis
data pengembangan `jhs_db` tidak tersentuh karena suite memakai `jhs_test`.

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS jhs_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan test -c phpunit.mysql.xml
```

Hasil terakhir: 55 test / 309 assertion pada MySQL 8.4.3, dengan
`ONLY_FULL_GROUP_BY` dan `STRICT_TRANS_TABLES` aktif.

Berkas `phpunit.xml` sengaja dibiarkan memakai SQLite in-memory agar suite
default tetap cepat; `phpunit.mysql.xml` hanya dipakai saat verifikasi
kompatibilitas produksi.

---

## 7. Perintah pemeliharaan

```bash
php artisan optimize:clear      # bersihkan semua cache
php artisan config:cache        # cache konfigurasi
php artisan route:cache         # cache route
php artisan view:cache          # cache Blade
php artisan db:seed             # isi data demo
php artisan migrate:fresh --seed  # RESET total, hanya untuk pengembangan
```
