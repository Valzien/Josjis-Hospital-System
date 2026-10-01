<?php

use App\Enums\UserRole;

return [
    /*
    |--------------------------------------------------------------------------
    | Identitas sistem
    |--------------------------------------------------------------------------
    */
    'name' => env('JHS_NAME', 'JOSJIS Hospital System'),
    'short_name' => env('JHS_SHORT_NAME', 'JHS'),
    'tagline' => env('JHS_TAGLINE', 'Hospital Information System'),
    'description' => env('JHS_DESCRIPTION', 'Sistem informasi rumah sakit terpadu untuk layanan pasien yang cepat, akurat, dan dapat diaudit.'),

    'contact' => [
        'address' => env('JHS_ADDRESS', 'Jl. Prototype No. 00, Kota Simulasi'),
        'phone' => env('JHS_PHONE', '(021) 000-0000'),
        'email' => env('JHS_EMAIL', 'info@josjis-hospital.test'),
        'hours' => env('JHS_HOURS', 'Senin - Sabtu, 08.00 - 20.00 WIB (IGDuty 24 jam)'),
    ],

    'queue_prefix' => env('JHS_QUEUE_PREFIX', 'A'),

    /*
    |--------------------------------------------------------------------------
    | Aturan antrean
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'estimated_minutes_per_patient' => (int) env('JHS_ESTIMATED_MINUTES', 10),
        'auto_call_refresh' => (int) env('JHS_AUTO_CALL_REFRESH', 30),
        'per_page' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Akun demo (hanya untuk lingkungan pengembangan / demo)
    |--------------------------------------------------------------------------
    */
    'demo_credentials_enabled' => (bool) env('JHS_DEMO_CREDENTIALS', true),
    'demo_accounts' => [
        'Admin' => ['email' => 'admin@josjis.test', 'password' => 'password'],
        'Resepsionis' => ['email' => 'resepsionis@josjis.test', 'password' => 'password'],
        'Dokter' => ['email' => 'dokter@josjis.test', 'password' => 'password'],
        'Apoteker' => ['email' => 'apoteker@josjis.test', 'password' => 'password'],
        'Pasien' => ['email' => 'pasien@josjis.test', 'password' => 'password'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Halaman publik
    |--------------------------------------------------------------------------
    */
    'landing' => [
        'sections' => [
            'layanan' => 'Layanan',
            'dokter' => 'Dokter',
            'fasilitas' => 'Fasilitas',
            'alur' => 'Alur Layanan',
            'kontak' => 'Kontak',
        ],
        'features' => [
            ['icon' => 'bi-person-plus', 'title' => 'Registrasi Pasien', 'text' => 'Pendaftaran terpusat dengan penomoran rekam medis otomatis dan verifikasi NIK.'],
            ['icon' => 'bi-list-ol', 'title' => 'Antrean Cerdas', 'text' => 'Nomor antrean real-time per dokter, pemanggilan berikutnya, dan posisi pasien.'],
            ['icon' => 'bi-heart-pulse', 'title' => 'Pemeriksaan Dokter', 'text' => 'Dokter mencatat keluhan, hasil pemeriksaan, diagnosis, dan tindakan.'],
            ['icon' => 'bi-capsule', 'title' => 'Farmasi Terintegrasi', 'text' => 'Resep diteruskan ke apoteker, stok berkurang otomatis, riwayat obat tersimpan.'],
            ['icon' => 'bi-journal-medical', 'title' => 'Riwayat Pemeriksaan', 'text' => 'Pasien memantau seluruh riwayat pemeriksaan dan resepnya kapan saja.'],
            ['icon' => 'bi-graph-up-arrow', 'title' => 'Laporan & Audit', 'text' => 'Laporan administrasi dengan filter rentang tanggal serta audit log aktivitas.'],
        ],
        'facilities' => [
            ['icon' => 'bi-building', 'name' => 'Ruang Poli Umum'],
            ['icon' => 'bi-heart-pulse', 'name' => 'Ruang IGDuty'],
            ['icon' => 'bi-capsule', 'name' => 'Farmasi 24 Jam'],
            ['icon' => 'bi-labs', 'name' => 'Laboratorium'],
            ['icon' => 'bi-radiology', 'name' => 'Radiologi'],
            ['icon' => 'bi-thermometer-sun', 'name' => 'Klinik Anak'],
            ['icon' => 'bi-person-hearts', 'name' => 'Klinik Kandungan'],
            ['icon' => 'bi-activity', 'name' => 'Rehabilitasi Medik'],
        ],
        'steps' => [
            ['title' => 'Registrasi', 'text' => 'Daftar akun sekali, lengkapi profil, dan biarkan resepsionis memverifikasi data.'],
            ['title' => 'Ambil Antrean', 'text' => 'Pilih dokter dan jadwal praktik, langsung mendapat nomor antrean.'],
            ['title' => 'Pemeriksaan', 'text' => 'Pantau nomor antrean, dipanggil dokter, lalu pemeriksaan dicatat di rekam medis.'],
            ['title' => 'Resep & Obat', 'text' => 'Resep dikirim ke apoteker dan obat diserahkan dengan stok terperbarui otomatis.'],
        ],
        'flow' => [
            ['title' => 'Registrasi', 'text' => 'Pasien mendaftar dan data diverifikasi oleh resepsionis.'],
            ['title' => 'Ambil Antrean', 'text' => 'Pasien memilih dokter dan jadwal, lalu menerima nomor antrean.'],
            ['title' => 'Pemeriksaan', 'text' => 'Resepsionis memanggil, dokter memeriksa dan menyimpan rekam medis.'],
            ['title' => 'Resep', 'text' => 'Dokter membuat resep berisi obat, dosis, dan aturan pakai.'],
            ['title' => 'Farmasi', 'text' => 'Apoteker mengecek stok, memproses, dan menyerahkan obat.'],
            ['title' => 'Riwayat', 'text' => 'Pasien melihat rekam medis, resep, dan obat yang diterima.'],
        ],
        'roles' => [
            ['role' => UserRole::Admin, 'icon' => 'bi-shield-lock', 'color' => 'primary', 'text' => 'Mengelola pengguna, master data, laporan, dan audit log sistem.'],
            ['role' => UserRole::Receptionist, 'icon' => 'bi-headset', 'color' => 'info', 'text' => 'Mendaftarkan pasien, membuat dan memanggil antrean.'],
            ['role' => UserRole::Doctor, 'icon' => 'bi-heart-pulse', 'color' => 'success', 'text' => 'Melakukan pemeriksaan, menulis diagnosis, dan membuat resep.'],
            ['role' => UserRole::Pharmacist, 'icon' => 'bi-capsule', 'color' => 'warning', 'text' => 'Memproses resep, mengelola obat, dan memantau stok.'],
            ['role' => UserRole::Patient, 'icon' => 'bi-person', 'color' => 'secondary', 'text' => 'Mengambil antrean online dan memantau riwayat pelayanan.'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sidebar per role
    |--------------------------------------------------------------------------
    | 'badge' menunjuk key pada array badge yang dikirim ke view.
    */
    'navigation' => [
        UserRole::Admin->value => [
            ['section' => 'Utama', 'label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'bi-speedometer2'],
            ['section' => 'Master Data', 'label' => 'Pengguna', 'route' => 'admin.users.index', 'icon' => 'bi-people'],
            ['label' => 'Dokter', 'route' => 'admin.doctors.index', 'icon' => 'bi-heart-pulse'],
            ['label' => 'Apoteker', 'route' => 'admin.pharmacists.index', 'icon' => 'bi-capsule'],
            ['label' => 'Resepsionis', 'route' => 'admin.receptionists.index', 'icon' => 'bi-headset'],
            ['label' => 'Pasien', 'route' => 'admin.patients.index', 'icon' => 'bi-person-vcard'],
            ['label' => 'Jadwal Dokter', 'route' => 'admin.schedules.index', 'icon' => 'bi-calendar-week'],
            ['section' => 'Monitoring', 'label' => 'Laporan', 'route' => 'admin.reports.index', 'icon' => 'bi-graph-up-arrow'],
            ['label' => 'Audit Log', 'route' => 'admin.audit-logs.index', 'icon' => 'bi-shield-check'],
            ['section' => 'Sistem', 'label' => 'Pengaturan', 'route' => 'admin.settings.index', 'icon' => 'bi-gear'],
        ],
        UserRole::Receptionist->value => [
            ['section' => 'Utama', 'label' => 'Dashboard', 'route' => 'reception.dashboard', 'icon' => 'bi-speedometer2'],
            ['section' => 'Pelayanan', 'label' => 'Registrasi Pasien', 'route' => 'reception.registration.create', 'icon' => 'bi-person-plus'],
            ['label' => 'Data Pasien', 'route' => 'reception.patients.index', 'icon' => 'bi-person-vcard'],
            ['label' => 'Antrean', 'route' => 'reception.queues.index', 'icon' => 'bi-list-ol', 'badge' => 'waiting'],
            ['label' => 'Papan Antrean', 'route' => 'reception.queues.board', 'icon' => 'bi-display'],
            ['label' => 'Jadwal Dokter', 'route' => 'reception.schedules.index', 'icon' => 'bi-calendar-week'],
        ],
        UserRole::Doctor->value => [
            ['section' => 'Utama', 'label' => 'Dashboard', 'route' => 'doctor.dashboard', 'icon' => 'bi-speedometer2'],
            ['section' => 'Pelayanan', 'label' => 'Antrean Saya', 'route' => 'doctor.queues.index', 'icon' => 'bi-list-ol', 'badge' => 'waiting'],
            ['label' => 'Pasien', 'route' => 'doctor.patients.index', 'icon' => 'bi-people'],
            ['section' => 'Klinis', 'label' => 'Pemeriksaan', 'route' => 'doctor.examinations.index', 'icon' => 'bi-clipboard2-pulse'],
            ['label' => 'Rekam Medis', 'route' => 'doctor.medical-records.index', 'icon' => 'bi-journal-medical'],
            ['label' => 'Resep', 'route' => 'doctor.prescriptions.index', 'icon' => 'bi-file-earmark-medical'],
            ['section' => 'Jadwal', 'label' => 'Jadwal Praktik', 'route' => 'doctor.schedules.index', 'icon' => 'bi-calendar-week'],
        ],
        UserRole::Pharmacist->value => [
            ['section' => 'Utama', 'label' => 'Dashboard', 'route' => 'pharmacy.dashboard', 'icon' => 'bi-speedometer2'],
            ['section' => 'Farmasi', 'label' => 'Resep Masuk', 'route' => 'pharmacy.prescriptions.index', 'icon' => 'bi-inbox', 'badge' => 'prescription'],
            ['label' => 'Data Obat', 'route' => 'pharmacy.medicines.index', 'icon' => 'bi-capsule'],
            ['label' => 'Stok Obat', 'route' => 'pharmacy.stock.index', 'icon' => 'bi-box-seam', 'badge' => 'low_stock'],
            ['label' => 'Transaksi Obat', 'route' => 'pharmacy.transactions.index', 'icon' => 'bi-arrow-left-right'],
        ],
        UserRole::Patient->value => [
            ['section' => 'Utama', 'label' => 'Dashboard', 'route' => 'patient.dashboard', 'icon' => 'bi-speedometer2'],
            ['section' => 'Pelayanan', 'label' => 'Ambil Antrean', 'route' => 'patient.queue.create', 'icon' => 'bi-ticket-perforated'],
            ['label' => 'Antrean Saya', 'route' => 'patient.queue.index', 'icon' => 'bi-list-ol'],
            ['label' => 'Dokter', 'route' => 'patient.doctors.index', 'icon' => 'bi-heart-pulse'],
            ['label' => 'Jadwal Dokter', 'route' => 'patient.schedules.index', 'icon' => 'bi-calendar-week'],
            ['section' => 'Rekam', 'label' => 'Riwayat Pemeriksaan', 'route' => 'patient.history.index', 'icon' => 'bi-journal-medical'],
            ['label' => 'Resep Saya', 'route' => 'patient.prescriptions.index', 'icon' => 'bi-file-earmark-medical'],
            ['label' => 'Obat Diberikan', 'route' => 'patient.medicines.index', 'icon' => 'bi-capsule'],
            ['section' => 'Akun', 'label' => 'Profil Saya', 'route' => 'patient.profile.edit', 'icon' => 'bi-person-gear'],
        ],
    ],
];
