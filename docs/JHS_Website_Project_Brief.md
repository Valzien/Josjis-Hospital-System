# JOSJIS Hospital System (JHS)

## Website Project Brief

## 1. Project Overview

**JOSJIS Hospital System (JHS)** adalah prototype sistem informasi rumah
sakit berbasis web untuk mengintegrasikan proses pelayanan pasien dalam
satu platform.

Website harus menghubungkan proses:

**Registrasi Pasien → Antrean → Pemeriksaan Dokter → Resep →
Farmasi/Obat → Riwayat Pemeriksaan**

Project ini adalah **prototype akademik**, bukan sistem produksi rumah
sakit dan bukan pengganti sistem klinis tersertifikasi.

Tujuan utama website: - Mengelola data pengguna dan pasien. - Mengelola
registrasi pasien. - Mengelola antrean pasien. - Mengelola jadwal
dokter. - Memfasilitasi pemeriksaan dokter. - Membuat dan memproses
resep. - Mengelola data serta stok obat. - Menyediakan riwayat
pemeriksaan pasien. - Menyediakan laporan administrasi sederhana. -
Menerapkan role-based access control.

------------------------------------------------------------------------

# 2. User Roles

Website memiliki 5 role utama:

1.  **Admin**
2.  **Resepsionis**
3.  **Dokter**
4.  **Apoteker**
5.  **Pasien**

Setiap role harus mempunyai dashboard dan menu yang berbeda sesuai
tugasnya.

------------------------------------------------------------------------

# 3. Role & Permissions

## 3.1 Admin

Admin adalah pengelola utama sistem.

### Fitur:

-   Dashboard
-   Mengelola user
-   Mengelola role/hak akses
-   Mengelola data dokter
-   Mengelola data apoteker
-   Mengelola data resepsionis
-   Mengelola data pasien
-   Mengelola data umum sistem
-   Melihat laporan
-   Melihat aktivitas/audit log

### Dashboard Admin

Tampilkan: - Total pasien - Total dokter - Total apoteker - Total
resepsionis - Antrean hari ini - Pemeriksaan hari ini - Resep hari ini -
Aktivitas terbaru

------------------------------------------------------------------------

# 4. Resepsionis

Resepsionis menangani pelayanan awal pasien.

### Fitur:

-   Dashboard
-   Registrasi pasien
-   Data pasien
-   Cari pasien
-   Membuat antrean
-   Memantau antrean
-   Memanggil antrean
-   Melihat status antrean
-   Melihat jadwal dokter

### Dashboard Resepsionis

Fokus pada: - Antrean hari ini - Pasien menunggu - Registrasi pasien -
Dokter yang sedang bertugas - Jadwal dokter - Tombol panggil antrean

Contoh status antrean:

``` text
WAITING
CALLED
IN EXAMINATION
COMPLETED
CANCELLED
```

------------------------------------------------------------------------

# 5. Dokter

Dokter menangani pemeriksaan pasien.

### Fitur:

-   Dashboard dokter
-   Melihat antrean pasien
-   Melihat pasien yang menunggu
-   Melihat detail pasien
-   Melakukan pemeriksaan
-   Mencatat hasil pemeriksaan
-   Mencatat diagnosis/hasil pemeriksaan
-   Melihat riwayat pemeriksaan
-   Membuat resep
-   Melihat resep yang pernah dibuat

### Dashboard Dokter

Tampilkan: - Jadwal hari ini - Jumlah pasien - Antrean saat ini - Pasien
berikutnya - Pemeriksaan yang sudah selesai

### Pemeriksaan

Dokter dapat mengisi: - Keluhan - Hasil pemeriksaan - Diagnosis/hasil
pemeriksaan - Catatan dokter - Tindakan - Resep jika diperlukan

Catatan:

Diagnosis pada project hanya berupa **pencatatan hasil pemeriksaan oleh
dokter**, bukan sistem pendukung keputusan klinis.

------------------------------------------------------------------------

# 6. Apoteker

Apoteker menangani resep dan obat.

### Fitur:

-   Dashboard apoteker
-   Melihat resep masuk
-   Melihat detail resep
-   Memproses resep
-   Data obat
-   Tambah/edit/hapus obat
-   Stok obat
-   Transaksi stok obat
-   Mencatat obat yang diberikan kepada pasien
-   Menandai resep selesai

### Dashboard Apoteker

Tampilkan: - Resep baru - Resep sedang diproses - Resep selesai - Obat
stok rendah - Total jenis obat - Transaksi obat hari ini

### Status resep

``` text
PENDING
PROCESSING
READY
COMPLETED
CANCELLED
```

------------------------------------------------------------------------

# 7. Pasien

Pasien menggunakan website untuk mendapatkan informasi pelayanan dan
memantau prosesnya.

### Fitur:

-   Register
-   Login
-   Profile
-   Melengkapi data diri
-   Melihat dokter
-   Melihat jadwal dokter
-   Mengambil nomor antrean online
-   Memantau antrean
-   Melihat riwayat pemeriksaan
-   Melihat resep
-   Melihat obat yang diberikan

### Dashboard Pasien

Contoh:

``` text
Halo, [Nama Pasien]

ANTREAN ANDA
Nomor       : A-023
Sedang      : A-019
Posisi      : 4
Status      : WAITING

[ Lihat Antrean ]

Dokter       | Jadwal       | Riwayat
12 dokter    | Hari ini     | 8 pemeriksaan
```

Tampilkan juga: - Pemeriksaan terakhir - Dokter terakhir - Resep
terbaru - Status antrean

------------------------------------------------------------------------

# 8. Main Business Flow

Alur utama sistem:

``` text
PATIENT REGISTRATION
        ↓
SELECT SERVICE / DOCTOR
        ↓
TAKE QUEUE NUMBER
        ↓
WAITING
        ↓
QUEUE CALLED
        ↓
DOCTOR EXAMINATION
        ↓
SAVE MEDICAL RECORD
        ↓
┌──────────────────────┐
│ NEED PRESCRIPTION?   │
└──────────┬───────────┘
           │
      YES  │  NO
           │
           ↓
      CREATE RECIPE
           ↓
      PHARMACY
           ↓
    CHECK MEDICINE STOCK
           ↓
      PROCESS RECIPE
           ↓
     MEDICINE GIVEN
           ↓
    SAVE MEDICATION DATA
           │
           └──────────────┐
                          ↓
                  PATIENT HISTORY
```

Admin dapat memonitor data administrasi dan laporan dari keseluruhan
proses.

------------------------------------------------------------------------

# 9. Main Website Pages

## Public Pages

``` text
/
├── Landing Page
├── Login
└── Register
```

Landing page dapat menjelaskan: - JHS - Layanan sistem - Cara kerja -
Informasi rumah sakit/prototype - Login/Register

------------------------------------------------------------------------

# 10. Authenticated Pages

## Admin

``` text
/admin/dashboard
/admin/users
/admin/doctors
/admin/pharmacists
/admin/receptionists
/admin/patients
/admin/reports
/admin/audit-logs
/admin/settings
```

## Resepsionis

``` text
/reception/dashboard
/reception/patients
/reception/registration
/reception/queues
/reception/schedules
```

## Dokter

``` text
/doctor/dashboard
/doctor/queue
/doctor/patients
/doctor/examinations
/doctor/medical-records
/doctor/prescriptions
```

## Apoteker

``` text
/pharmacy/dashboard
/pharmacy/prescriptions
/pharmacy/medicines
/pharmacy/stock
/pharmacy/transactions
```

## Pasien

``` text
/patient/dashboard
/patient/profile
/patient/doctors
/patient/schedules
/patient/queue
/patient/history
/patient/prescriptions
/patient/medicines
```

------------------------------------------------------------------------

# 11. Database Design

Database menggunakan relational database.

Entitas utama:

``` text
users
patients
doctors
receptionists
pharmacists
doctor_schedules
queues
medical_records
prescriptions
prescription_details
medicines
medicine_transactions
audit_logs
```

## Relasi utama

``` text
users
 │
 ├── patients
 ├── doctors
 ├── receptionists
 └── pharmacists

doctors
 │
 └── doctor_schedules

patients
 │
 ├── queues
 └── medical_records

doctor_schedules
 │
 └── queues

medical_records
 │
 └── prescriptions
       │
       └── prescription_details
                │
                └── medicines

medicines
 │
 └── medicine_transactions
```

Database harus menggunakan: - Primary key - Foreign key - Timestamp -
Foreign key constraints - Relasi yang jelas - Validasi data - Soft
delete jika memang diperlukan

------------------------------------------------------------------------

# 12. Suggested Important Data

## users

``` text
id
name
email
password
role
status
created_at
updated_at
```

Role:

``` text
admin
receptionist
doctor
pharmacist
patient
```

## patients

``` text
id
user_id
medical_record_number
nik
name
gender
birth_date
phone
address
blood_type
created_at
updated_at
```

## doctors

``` text
id
user_id
doctor_code
name
specialization
phone
status
created_at
updated_at
```

## doctor_schedules

``` text
id
doctor_id
day
start_time
end_time
room
status
```

## queues

``` text
id
patient_id
doctor_id
doctor_schedule_id
queue_number
queue_date
status
called_at
completed_at
```

## medical_records

``` text
id
patient_id
doctor_id
queue_id
complaint
examination_result
diagnosis
notes
examined_at
```

## prescriptions

``` text
id
medical_record_id
patient_id
doctor_id
status
notes
created_at
processed_at
```

## prescription_details

``` text
id
prescription_id
medicine_id
quantity
dosage
instructions
```

## medicines

``` text
id
medicine_code
name
category
unit
stock
minimum_stock
price
description
status
```

## medicine_transactions

``` text
id
medicine_id
type
quantity
reference_type
reference_id
notes
created_at
```

Type:

``` text
IN
OUT
ADJUSTMENT
```

## audit_logs

Digunakan untuk mencatat aktivitas penting.

``` text
id
user_id
action
module
description
ip_address
created_at
```

------------------------------------------------------------------------

# 13. Authentication & Authorization

Implementasikan authentication dan role-based authorization.

Requirements:

-   Login
-   Logout
-   Register untuk pasien
-   Session/authentication
-   Role-based middleware
-   Route protection
-   User hanya dapat mengakses fitur sesuai role
-   Validasi input
-   Password harus disimpan secara aman
-   Jangan expose password di API/view
-   Data pasien hanya dapat diakses sesuai kebutuhan role

Contoh:

``` text
/admin/*       → admin only
/reception/*   → receptionist only
/doctor/*      → doctor only
/pharmacy/*    → pharmacist only
/patient/*     → patient only
```

------------------------------------------------------------------------

# 14. UI/UX Direction

Website harus terlihat seperti **modern hospital management system**,
bukan website CRUD sederhana.

Style yang diinginkan:

-   Clean
-   Professional
-   Modern
-   Medical/hospital aesthetic
-   Responsive
-   Desktop-first tetapi tetap usable di tablet/mobile
-   Sidebar navigation
-   Topbar
-   Dashboard cards
-   Data tables
-   Modal
-   Form
-   Badge/status
-   Toast notification
-   Confirmation dialog
-   Empty state
-   Loading state
-   Error state

## Layout

Gunakan pola:

``` text
┌───────────────────────────────────────────────┐
│ SIDEBAR │ TOPBAR                              │
│         ├─────────────────────────────────────┤
│         │                                     │
│ MENU    │             CONTENT                 │
│         │                                     │
│         │                                     │
│         │                                     │
└─────────┴─────────────────────────────────────┘
```

Sidebar harus berubah sesuai role.

------------------------------------------------------------------------

# 15. Dashboard Design

Dashboard jangan hanya berisi angka.

Gunakan kombinasi:

-   Summary cards
-   Charts
-   Recent activity
-   Tables
-   Status badges
-   Quick actions
-   Notifications
-   Today's schedule
-   Queue status

Contoh Admin:

``` text
┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│ Patients │ │ Doctors  │ │ Queues   │ │ Exams    │
│   1,245  │ │    24    │ │    87    │ │    63    │
└──────────┘ └──────────┘ └──────────┘ └──────────┘

┌───────────────────────┐ ┌───────────────────────┐
│ Patient Statistics    │ │ Today's Activity      │
│                       │ │                       │
│       CHART           │ │ Recent activities    │
│                       │ │                       │
└───────────────────────┘ └───────────────────────┘
```

------------------------------------------------------------------------

# 16. Queue System

Antrean merupakan salah satu fitur utama.

Sistem harus bisa:

-   Generate nomor antrean
-   Menampilkan nomor antrean
-   Menampilkan posisi pasien
-   Memanggil pasien berikutnya
-   Mengubah status
-   Menampilkan antrean yang sedang dipanggil
-   Menampilkan antrean selesai
-   Memisahkan antrean berdasarkan dokter/jadwal
-   Menampilkan antrean berdasarkan tanggal

Contoh:

``` text
POLI UMUM

NOW SERVING
A-019

NEXT
A-020
A-021
A-022

YOUR QUEUE
A-023
Position #4
```

------------------------------------------------------------------------

# 17. Medical Record

Setiap pemeriksaan dokter harus menghasilkan medical record.

Data minimal:

``` text
Patient
Doctor
Date
Complaint
Examination Result
Diagnosis
Notes
Prescription
```

Pasien dapat melihat riwayat pemeriksaannya.

Dokter dapat melihat riwayat pasien sesuai kebutuhan sistem.

------------------------------------------------------------------------

# 18. Prescription & Pharmacy Flow

Dokter membuat resep dari halaman pemeriksaan.

Contoh:

``` text
Prescription
────────────────────────

Paracetamol
Quantity: 10
Dosage: 3x1
Instruction: After meal

Amoxicillin
Quantity: 10
Dosage: 3x1
Instruction: After meal
```

Kemudian:

``` text
Doctor creates prescription
        ↓
Prescription status = PENDING
        ↓
Pharmacist sees incoming prescription
        ↓
Check medicine stock
        ↓
Process prescription
        ↓
Medicine given
        ↓
Stock reduced
        ↓
Prescription = COMPLETED
```

Stock obat harus berubah berdasarkan transaksi obat.

------------------------------------------------------------------------

# 19. Reports

Laporan sederhana sesuai scope project.

Minimal:

-   Laporan pasien
-   Laporan antrean
-   Laporan pemeriksaan
-   Laporan resep
-   Laporan obat
-   Laporan transaksi obat

Filter:

``` text
Date range
Doctor
Patient
Status
Medicine
```

Jika memungkinkan, sediakan export:

``` text
PDF
Excel
```

------------------------------------------------------------------------

# 20. Notifications

Gunakan notification/toast untuk aktivitas seperti:

-   Login berhasil
-   Data berhasil dibuat
-   Data berhasil diubah
-   Data berhasil dihapus
-   Antrean berhasil dibuat
-   Antrean dipanggil
-   Pemeriksaan berhasil disimpan
-   Resep berhasil dibuat
-   Resep selesai diproses
-   Stok obat rendah

------------------------------------------------------------------------

# 21. Important UX States

Semua halaman harus menangani:

### Loading

``` text
Loading data...
```

### Empty

``` text
Belum ada data.
```

### Error

``` text
Terjadi kesalahan.
Silakan coba lagi.
```

### Success

Gunakan toast/notification.

### Confirmation

Untuk tindakan destructive:

``` text
Apakah Anda yakin ingin menghapus data ini?
[Cancel] [Delete]
```

------------------------------------------------------------------------

# 22. Security Requirements

Karena sistem mengelola data pasien, minimal implementasikan:

-   Authentication
-   Authorization
-   Role-based access
-   Input validation
-   CSRF protection jika menggunakan session/web auth
-   Password hashing
-   Protected routes
-   Server-side validation
-   Audit log untuk aktivitas penting
-   Jangan menggunakan data pasien nyata

Semua data untuk development/testing harus berupa **data simulasi**.

------------------------------------------------------------------------

# 23. Project Scope Limitations

Jangan implementasikan fitur berikut karena berada di luar scope
proposal:

-   BPJS integration
-   Full hospital financial system
-   Payment gateway
-   Laboratory system
-   Radiology system
-   Telemedicine
-   Medical device integration
-   National health system integration
-   Full inpatient management
-   Real clinical decision support

Fokus pada:

``` text
Registration
Queue
Doctor Schedule
Examination
Medical Record
Prescription
Medicine
Medicine Stock
Users
Reports
```

------------------------------------------------------------------------

# 24. Recommended Development Priority

Jangan mengembangkan semua halaman sekaligus.

Urutan implementasi:

## Phase 1 --- Foundation

``` text
Project setup
Database
Authentication
User roles
Role middleware
Main layout
Sidebar
```

## Phase 2 --- Master Data

``` text
Users
Patients
Doctors
Pharmacists
Receptionists
Medicines
Doctor schedules
```

## Phase 3 --- Patient Registration & Queue

``` text
Patient registration
Doctor schedule
Take queue
Queue management
Queue status
Call queue
```

## Phase 4 --- Doctor

``` text
Doctor dashboard
Patient queue
Patient detail
Medical examination
Medical record
Prescription
```

## Phase 5 --- Pharmacy

``` text
Incoming prescription
Medicine management
Stock
Medicine transactions
Prescription processing
Medicine delivery
```

## Phase 6 --- Patient Portal

``` text
Patient dashboard
Profile
Queue monitoring
Schedule
Medical history
Prescription
Medicine history
```

## Phase 7 --- Admin & Reports

``` text
Admin dashboard
Reports
Audit logs
System monitoring
```

## Phase 8 --- Polish

``` text
Responsive UI
Loading states
Empty states
Error handling
Toast
Confirmation modal
Validation
Security review
Testing
```

------------------------------------------------------------------------

# 25. Technical Direction

Proposal awal menggunakan:

-   **Laravel** --- backend/framework utama
-   **PHP**
-   **Blade** --- frontend/template
-   **Bootstrap** --- UI
-   **MySQL** --- database
-   **Laravel Authentication**
-   **Middleware** --- role-based authorization
-   **Figma** --- UI/UX design
-   **Git/GitHub** --- version control

Arsitektur:

``` text
Browser
   ↓
Blade / Bootstrap
   ↓
Laravel Routes
   ↓
Controllers
   ↓
Services / Business Logic
   ↓
Eloquent Models
   ↓
MySQL
```

Gunakan MVC Laravel secara terstruktur.

------------------------------------------------------------------------

# 26. Important Implementation Rule

Jangan membuat website hanya sebagai kumpulan CRUD.

Fokus utama project adalah **integrasi antar modul**.

Contoh:

``` text
Patient
   ↓
Queue
   ↓
Doctor
   ↓
Medical Record
   ↓
Prescription
   ↓
Pharmacy
   ↓
Medicine Stock
   ↓
Patient History
```

Data dari satu proses harus dapat digunakan oleh proses berikutnya tanpa
input ulang yang tidak diperlukan.

------------------------------------------------------------------------

# 27. Expected Final Product

Hasil akhir harus berupa website prototype JHS yang dapat
didemonstrasikan dari awal sampai akhir.

Demo scenario:

``` text
1. Login sebagai Admin
2. Admin membuat dokter
3. Admin membuat akun/role yang diperlukan
4. Dokter memiliki jadwal
5. Login sebagai Pasien
6. Pasien melengkapi profil
7. Pasien memilih dokter
8. Pasien mengambil nomor antrean
9. Login sebagai Resepsionis
10. Resepsionis melihat antrean
11. Resepsionis memanggil pasien
12. Login sebagai Dokter
13. Dokter melihat pasien
14. Dokter melakukan pemeriksaan
15. Dokter menyimpan medical record
16. Dokter membuat resep
17. Login sebagai Apoteker
18. Apoteker menerima resep
19. Apoteker mengecek stok
20. Apoteker memproses resep
21. Stok obat berkurang
22. Resep menjadi COMPLETED
23. Login kembali sebagai Pasien
24. Pasien melihat riwayat pemeriksaan
25. Pasien melihat resep dan obat
26. Admin dapat melihat laporan
```

**Intinya: buat JHS sebagai satu sistem rumah sakit yang saling
terhubung, bukan lima dashboard yang berdiri sendiri.**
