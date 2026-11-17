<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="scroll-behavior: smooth;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('jhs.name') }} &middot; {{ config('jhs.tagline') }}</title>
    <meta name="description" content="{{ config('jhs.description') }}">

    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Inter+Tight:ital,wght@0,300..900;1,300..900&family=Parkinsans:wght@300..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jhs.css').'?v='.@filemtime(public_path('css/jhs.css')) }}">
</head>
<body class="jhs-body jhs-landing bg-white position-relative" style="overflow-x: hidden;">

<!-- Adievia Floating Pill Navbar (Clean Logo, No Chevrons, Micro-interactive Animations) -->
<header id="site-header" class="adievia-header">
    <div class="adievia-navbar">
        <!-- Logo (Only Icon as requested, with hover & click animations) -->
        <a href="{{ route('landing') }}" class="adievia-logo-link" title="{{ config('jhs.name') }}">
            <img src="{{ asset('images/logo-icon.png') }}" alt="{{ config('jhs.name') }}" height="34" class="d-inline-block">
        </a>

        <!-- Vertical Divider (Sekat ala Adievia) -->
        <div class="adievia-logo-divider d-none d-lg-block"></div>

        <!-- Desktop Menu Links (Clean, No Down Arrows, Interactive Hover & Active Animations) -->
        <nav class="d-none d-lg-flex align-items-center gap-1">
            <a href="#layanan" class="adievia-nav-link">Tentang</a>
            <a href="#layanan-pasien" class="adievia-nav-link">Layanan Pasien</a>
            <a href="#dokter" class="adievia-nav-link">Dokter Spesialis</a>
            <a href="#fasilitas" class="adievia-nav-link">Fasilitas Rawat Inap</a>
            <a href="#alur" class="adievia-nav-link">Alur Pasien</a>
            <a href="#kontak" class="adievia-nav-link">Kontak</a>
        </nav>

        <!-- Right Side: Daftar, and Pill CTA Masuk (No Moon Button) -->
        <div class="d-flex align-items-center gap-2 gap-md-2.5">
            <a href="{{ route('register') }}" class="adievia-btn-pill-outline d-none d-sm-inline-flex jhs-btn-interactive" style="font-size: 0.78rem; padding: 0.42rem 1.15rem;">
                <span>Daftar</span>
            </a>
            <a href="{{ route('login') }}" class="adievia-btn-pill jhs-btn-interactive" style="font-size: 0.78rem; padding: 0.45rem 1.35rem;">
                <span>Masuk</span>
            </a>
            <!-- Mobile Menu Toggle -->
            <button class="btn btn-sm btn-light border rounded-pill d-lg-none px-2.5 py-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                <i class="bi bi-list fs-5"></i>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Offcanvas Menu -->
<div class="offcanvas offcanvas-end rounded-start-4" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="mobileMenuLabel">Menu Layanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav flex-column gap-2 mb-4">
            <li class="nav-item"><a class="nav-link text-dark fw-bold" href="#layanan" data-bs-dismiss="offcanvas">Tentang Layanan</a></li>
            <li class="nav-item"><a class="nav-link text-dark fw-bold" href="#layanan-pasien" data-bs-dismiss="offcanvas">Layanan Pasien</a></li>
            <li class="nav-item"><a class="nav-link text-dark fw-bold" href="#dokter" data-bs-dismiss="offcanvas">Dokter Spesialis</a></li>
            <li class="nav-item"><a class="nav-link text-dark fw-bold" href="#fasilitas" data-bs-dismiss="offcanvas">Fasilitas Rawat Inap</a></li>
            <li class="nav-item"><a class="nav-link text-dark fw-bold" href="#alur" data-bs-dismiss="offcanvas">Alur Pelayanan</a></li>
            <li class="nav-item"><a class="nav-link text-dark fw-bold" href="#kontak" data-bs-dismiss="offcanvas">Kontak &amp; Lokasi</a></li>
        </ul>
        <div class="d-grid gap-2 border-top pt-3">
            <a href="{{ route('register') }}" class="adievia-btn-pill w-100 py-2.5 fw-semibold text-center justify-content-center">Daftar</a>
            <a href="{{ route('login') }}" class="adievia-btn-pill-outline w-100 py-2.5 fw-semibold text-center justify-content-center">Masuk Akun Pasien</a>
        </div>
    </div>
</div>

@include('partials.flash')

<!-- Hero Section: Left-Aligned Text with Cinematic Hospital Motion Video Slide in Background -->
<header class="jhs-hero-modern py-5 position-relative" style="min-height: 88svh; display: flex; align-items: center; padding-top: 6.5rem !important;">
    <!-- Cinematic Transparent Hospital Video Slideshow in Background -->
    <div class="jhs-hero-slider-bg">
        <div class="jhs-hero-slide" style="background-image: url('{{ asset('images/hospital-slide-1.jpg') }}'); animation-delay: 0s;"></div>
        <div class="jhs-hero-slide" style="background-image: url('{{ asset('images/hospital-slide-2.jpg') }}'); animation-delay: 6s;"></div>
        <div class="jhs-hero-slide" style="background-image: url('{{ asset('images/hospital-slide-3.jpg') }}'); animation-delay: 12s;"></div>
        <div class="jhs-hero-slide-overlay"></div>
    </div>

    <!-- Tilted Floating UI Mockup Cards in Background (Signature Adievia Tech SaaS Vibe) -->
    <div class="hero-mockup-bg" style="opacity: 0.12;">
        <div class="hero-mockup-reel">
            <!-- Row 1 Mockups -->
            <div class="hero-mockup-row">
                <div class="hero-mockup-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary small">SIMRS Rawat Inap</span>
                        <span class="text-muted small">09.30 WIB</span>
                    </div>
                    <div class="fw-bold text-dark small mb-1">Papan Antrean Poliklinik</div>
                    <div class="p-2 rounded-2 bg-light d-flex justify-content-between small">
                        <span>Poli Saraf &middot; dr. Maya Lutfiani</span>
                        <span class="badge bg-success">A-013 Dipanggil</span>
                    </div>
                </div>

                <div class="hero-mockup-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-success bg-opacity-10 text-success small">Rekam Medis Elektronik</span>
                        <span class="text-muted small">RME No. RM-00291</span>
                    </div>
                    <div class="fw-bold text-dark small mb-1">Diagnosa &amp; Tanda Vital Pasien</div>
                    <div class="text-secondary small">Tekanan Darah: 120/80 mmHg &middot; Suhu: 36.5&deg;C</div>
                </div>

                <div class="hero-mockup-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-warning bg-opacity-10 text-warning small">Instalasi Farmasi</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary">SEDANG DIRACIK</span>
                    </div>
                    <div class="fw-bold text-dark small mb-1">Resep Digital #RX-202611-001</div>
                    <div class="text-secondary small">Amoxicillin 500mg (10 tab) &middot; Stok Terpotong</div>
                </div>
            </div>

            <!-- Row 2 Mockups -->
            <div class="hero-mockup-row" style="margin-left: -60px;">
                <div class="hero-mockup-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-danger bg-opacity-10 text-danger small">Billing &amp; Kasir</span>
                        <span class="badge bg-success">LUNAS</span>
                    </div>
                    <div class="fw-bold text-dark small mb-1">Kuitansi Pembayaran Resmi</div>
                    <div class="text-secondary small">Konsultasi Poli Spesialis + Resep Obat Terbayar</div>
                </div>

                <div class="hero-mockup-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary small">Tiket Antrean Mandiri</span>
                        <span class="text-muted small">Slip Thermal 58mm</span>
                    </div>
                    <div class="fw-bold text-dark small mb-1">No. Antrean: A-001</div>
                    <div class="text-secondary small">Status: Menunggu Panggilan Loket Resepsionis</div>
                </div>

                <div class="hero-mockup-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-info bg-opacity-10 text-info small">Audit Rekam Medis</span>
                        <span class="text-muted small">Log Aktivitas</span>
                    </div>
                    <div class="fw-bold text-dark small mb-1">Otorisasi Berbasis Peran</div>
                    <div class="text-secondary small">Setiap akses rekam medis &amp; obat tercatat aman</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hero Content: Text Left Aligned -->
    <div class="container py-4 position-relative" style="z-index: 2;">
        <div class="row align-items-center">
            <!-- Left Column: Headline, Description & CTAs -->
            <div class="col-lg-10 col-xl-9 text-start">
                
                <!-- Eyebrow Tag (Clean, No Blinking Dot) -->
                <div class="mb-3 reveal">
                    <span class="badge border bg-white text-primary rounded-pill px-3.5 py-1.5 fw-bold shadow-xs" 
                          style="font-size: 0.76rem; letter-spacing: 0.08em; border-color: rgba(13, 110, 253, 0.25) !important;">
                        PELAYANAN PASIEN RAWAT INAP TERPADU
                    </span>
                </div>

                <!-- Giant 3-Line Modern Headline (Parkinsans Font, Left Aligned) -->
                <h1 class="jhs-caps mb-3 reveal delay-100" style="line-height: 0.94;">
                    <span class="d-block text-slate-900" style="font-size: clamp(2.3rem, 6.5vw, 4.8rem); font-weight: 800;">PELAYANAN PASIEN</span>
                    <span class="d-block my-1" style="font-size: clamp(3rem, 10vw, 7.2rem); font-weight: 800; color: #0d6efd; letter-spacing: -0.03em;">RAWAT INAP</span>
                    <span class="d-block text-slate-900" style="font-size: clamp(2.3rem, 6.5vw, 4.8rem); font-weight: 800;">BEBAS ANTREAN PANJANG</span>
                </h1>

                <!-- Subtitle (Authentic Indonesian Outpatient Flow) -->
                <p class="lead text-secondary mb-4 reveal delay-150" style="font-size: clamp(1rem, 2vw, 1.2rem); max-width: 680px; line-height: 1.68; font-weight: 450;">
                    Ambil nomor antrean mandiri secara praktis dari rumah, pantau giliran secara langsung, dan nikmati alur pemeriksaan terpadu — mulai dari verifikasi loket pendaftaran, pemeriksaan dokter spesialis, farmasi obat, hingga pembayaran kasir tanpa antre berdesakan.
                </p>

                <!-- Action Buttons: "Daftar" and "Masuk Akun Pasien" with Micro-interactive Motion & Neat Spacing -->
                <div class="d-flex flex-wrap align-items-center gap-3 mb-4 reveal delay-200">
                    <a href="{{ route('register') }}" class="adievia-btn-pill jhs-btn-interactive py-2.5 px-4 shadow-sm" style="font-size: 0.9rem;">
                        <span>Daftar</span>
                        <i class="bi bi-arrow-right ms-2 jhs-btn-arrow"></i>
                    </a>
                    <a href="{{ route('login') }}" class="adievia-btn-pill-outline jhs-btn-interactive py-2.5 px-4 shadow-xs" style="font-size: 0.9rem;">
                        <i class="bi bi-box-arrow-in-right me-2 jhs-btn-icon"></i>
                        <span>Masuk Akun Pasien</span>
                    </a>
                </div>

                <!-- Value Highlights (Left Aligned) -->
                <div class="d-flex flex-wrap align-items-center gap-3 gap-md-4 text-secondary small pt-2 reveal delay-250">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="fw-medium text-slate-700">Antrean Mandiri &amp; Real-Time</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="fw-medium text-slate-700">Pemeriksaan Dokter Terpadu</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="fw-medium text-slate-700">E-Resep Farmasi Cepat</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="fw-medium text-slate-700">Billing Kasir &amp; Kuitansi Resmi</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</header>

<!-- Infinite Marquee Ticker (Pita Berjalan Adievia) -->
<div class="jhs-marquee-wrap">
    <div class="jhs-marquee-track">
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Registrasi Pasien &amp; No. RM Otomatis</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Antrean Mandiri Berbasis Kuota &amp; Jam WIB</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Gate Verifikasi Loket Resepsionis</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Cetak Karcis Thermal 58mm / 80mm</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Pemeriksaan Fisik &amp; Rekam Medis Dokter</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> E-Resep Digital &amp; Pemotongan Stok Atomik</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Kasir, Invoicing &amp; Kuitansi Resmi</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Audit Trail &amp; Keamanan Akses Berbasis Peran</span>

        <!-- Duplicated for seamless infinite loop -->
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Registrasi Pasien &amp; No. RM Otomatis</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Antrean Mandiri Berbasis Kuota &amp; Jam WIB</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Gate Verifikasi Loket Resepsionis</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Cetak Karcis Thermal 58mm / 80mm</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Pemeriksaan Fisik &amp; Rekam Medis Dokter</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> E-Resep Digital &amp; Pemotongan Stok Atomik</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Kasir, Invoicing &amp; Kuitansi Resmi</span>
        <span class="jhs-marquee-item"><i class="bi bi-star-fill"></i> Audit Trail &amp; Keamanan Akses Berbasis Peran</span>
    </div>
</div>

<!-- Section: Kemudahan Layanan Pasien & Integrasi Rumah Sakit (Replaces Portal Pasien & Petugas) -->
<section id="pintu" class="py-5" style="background: #f8fafc;">
    <div id="layanan-pasien" style="position: relative; top: -80px;"></div>
    <div class="container py-lg-4">
        <div class="text-center mb-5 reveal">
            <span class="badge border bg-white text-primary rounded-pill px-3 py-1.5 fw-bold small mb-2 shadow-xs" style="border-color: rgba(13, 110, 253, 0.3) !important;">
                Layanan Unggulan &middot; Rawat Inap Terpadu
            </span>
            <h2 class="jhs-caps fw-black text-slate-900 mt-2 mb-2" style="font-size: clamp(2rem, 5vw, 3.2rem);">KEMUDAHAN PASIEN &amp; INTEGRASI LAYANAN</h2>
            <p class="text-secondary mx-auto mb-0" style="max-width: 580px;">
                Seluruh tahapan pelayanan rawat inap dirancang terpadu — memudahkan pasien mendaftar dan mengambil antrean mandiri, sekaligus mempercepat alur dokter, farmasi, dan kasir.
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <!-- Card 1: Pendaftaran & Antrean Pasien Mandiri -->
            <div class="col-lg-6 reveal delay-100">
                <div class="jhs-door-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="jhs-door-icon">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1.5 rounded-pill fw-semibold small">
                            Layanan Pasien Mandiri
                        </span>
                    </div>

                    <h3 class="h4 fw-bold text-slate-900 mb-2">Pendaftaran &amp; Antrean Mandiri</h3>
                    <p class="text-secondary small mb-4" style="line-height: 1.6;">
                        Kemudahan mendaftar dan mengambil nomor antrean rawat inap dari rumah tanpa perlu datang subuh untuk berdesakan di loket rumah sakit.
                    </p>

                    <ul class="list-unstyled mb-4 flex-grow-1">
                        <li class="d-flex align-items-center gap-2 mb-2.5 text-slate-800 small">
                            <i class="bi bi-check2-circle text-primary fs-5"></i>
                            <span><strong>Pendaftaran Pasien Baru Online:</strong> Daftar mandiri secara praktis dan otomatis dapat Nomor Rekam Medis (No. RM).</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 mb-2.5 text-slate-800 small">
                            <i class="bi bi-check2-circle text-primary fs-5"></i>
                            <span><strong>Ambil Nomor Antrean dari Rumah:</strong> Pilih poli spesialis dan slot jam praktik dokter secara transparan.</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 mb-2.5 text-slate-800 small">
                            <i class="bi bi-check2-circle text-primary fs-5"></i>
                            <span><strong>Pantau Giliran Real-Time:</strong> Status antrean terpantau langsung lewat HP sehingga waktu Anda tidak terbuang.</span>
                        </li>
                        <li class="d-flex align-items-center gap-2 text-slate-800 small">
                            <i class="bi bi-check2-circle text-primary fs-5"></i>
                            <span><strong>Riwayat Medis &amp; E-Resep:</strong> Seluruh riwayat kunjungan dan petunjuk minum obat tersimpan rapi.</span>
                        </li>
                    </ul>

                    <div class="d-flex flex-wrap gap-2.5 pt-3 border-top">
                        <a href="{{ route('register') }}" class="adievia-btn-pill jhs-btn-interactive flex-grow-1 text-center justify-content-center py-2.5 px-3">
                            <span>Daftar</span>
                            <i class="bi bi-arrow-right ms-2 jhs-btn-arrow"></i>
                        </a>
                        <a href="{{ route('login') }}" class="adievia-btn-pill-outline jhs-btn-interactive py-2.5 px-3.5 shadow-xs" style="font-size: 0.85rem;">
                            <i class="bi bi-box-arrow-in-right me-2 jhs-btn-icon"></i>
                            <span>Masuk Akun Pasien</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 2: Pelayanan Dokter, Farmasi & Kasir Terpadu -->
            <div class="col-lg-6 reveal delay-200">
                <div class="jhs-door-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="jhs-door-icon" style="background: rgba(2, 132, 199, 0.08); color: #0284c7;">
                            <i class="bi bi-hospital-fill"></i>
                        </div>
                        <span class="badge rounded-pill px-3 py-1.5 fw-semibold small" style="background: rgba(2, 132, 199, 0.1); color: #0284c7;">
                            Alur Medis Terpadu
                        </span>
                    </div>

                    <h3 class="h4 fw-bold text-slate-900 mb-2">Pemeriksaan Dokter &amp; Farmasi Cepat</h3>
                    <p class="text-secondary small mb-4" style="line-height: 1.6;">
                        Alur koordinasi klinis dokter spesialis, peracikan obat apotek, dan kasir yang terhubung otomatis dalam satu sistem terpadu.
                    </p>

                    <div class="row g-2.5 mb-4 flex-grow-1">
                        <div class="col-6">
                            <div class="jhs-flow-step-box h-100">
                                <div class="d-flex align-items-center mb-1.5">
                                    <span class="jhs-flow-icon-badge bg-primary bg-opacity-10 text-primary me-2">
                                        <i class="bi bi-headset"></i>
                                    </span>
                                    <span class="fw-bold text-slate-900" style="font-size: 0.86rem;">Resepsionis</span>
                                </div>
                                <div class="text-secondary small" style="font-size: 0.76rem; line-height: 1.35; padding-left: 2px;">
                                    Check-in &amp; cetak karcis
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="jhs-flow-step-box h-100">
                                <div class="d-flex align-items-center mb-1.5">
                                    <span class="jhs-flow-icon-badge bg-success bg-opacity-10 text-success me-2">
                                        <i class="bi bi-heart-pulse"></i>
                                    </span>
                                    <span class="fw-bold text-slate-900" style="font-size: 0.86rem;">Dokter Spesialis</span>
                                </div>
                                <div class="text-secondary small" style="font-size: 0.76rem; line-height: 1.35; padding-left: 2px;">
                                    Pemeriksaan &amp; E-Resep
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="jhs-flow-step-box h-100">
                                <div class="d-flex align-items-center mb-1.5">
                                    <span class="jhs-flow-icon-badge me-2" style="background: rgba(245, 158, 11, 0.12); color: #d97706 !important;">
                                        <i class="bi bi-capsule"></i>
                                    </span>
                                    <span class="fw-bold text-slate-900" style="font-size: 0.86rem;">Instalasi Farmasi</span>
                                </div>
                                <div class="text-secondary small" style="font-size: 0.76rem; line-height: 1.35; padding-left: 2px;">
                                    Peracikan obat digital
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="jhs-flow-step-box h-100">
                                <div class="d-flex align-items-center mb-1.5">
                                    <span class="jhs-flow-icon-badge bg-danger bg-opacity-10 text-danger me-2">
                                        <i class="bi bi-cash-coin"></i>
                                    </span>
                                    <span class="fw-bold text-slate-900" style="font-size: 0.86rem;">Kasir &amp; Billing</span>
                                </div>
                                <div class="text-secondary small" style="font-size: 0.76rem; line-height: 1.35; padding-left: 2px;">
                                    Pelunasan &amp; kuitansi resmi
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-top">
                        <a href="#dokter" class="adievia-btn-pill-outline jhs-btn-interactive w-100 text-center justify-content-center py-2.5 px-3.5 shadow-xs" style="font-size: 0.85rem;">
                            <span>Lihat Dokter Spesialis</span>
                            <i class="bi bi-arrow-right ms-2 jhs-btn-arrow"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section: Fondasi Layanan Utama (Adievia Core Foundation Cards) -->
<section id="layanan" class="py-5 bg-white">
    <div class="container py-lg-4">
        <div class="text-center mb-5 reveal">
            <span class="badge border bg-white text-primary rounded-pill px-3 py-1.5 fw-bold small mb-2 shadow-xs" style="border-color: rgba(13, 110, 253, 0.3) !important;">
                FONDASI SISTEM
            </span>
            <h2 class="jhs-caps fw-black text-slate-900 mt-2 mb-2" style="font-size: clamp(2rem, 5vw, 3.2rem);">FONDASI YANG SAMA</h2>
            <p class="text-secondary mx-auto mb-0" style="max-width: 540px;">
                Modul-modul terintegrasi yang menjamin data pasien aman, alur pelayanan tertib, dan tanpa redundansi input.
            </p>
        </div>

        <div class="row g-4">
            @foreach (config('jhs.landing.features') as $index => $feature)
                <div class="col-md-6 col-lg-4 reveal delay-{{ ($index % 3 + 1) * 100 }}">
                    <div class="jhs-card-adievia">
                        <div class="card-icon">
                            <i class="bi {{ $feature['icon'] }}"></i>
                        </div>
                        <h3 class="h6 fw-bold text-slate-900 mb-2">{{ $feature['title'] }}</h3>
                        <p class="small text-secondary mb-0" style="line-height: 1.6;">{{ $feature['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Section: Tim Dokter Spesialis -->
<section id="dokter" class="py-5" style="background: #f8fafc;">
    <div class="container py-lg-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4 reveal">
            <div>
                <span class="badge border bg-white text-primary rounded-pill px-3 py-1.5 fw-bold small mb-2 shadow-xs" style="border-color: rgba(13, 110, 253, 0.3) !important;">
                    TENAGA MEDIS
                </span>
                <h2 class="jhs-caps fw-black text-slate-900 mt-2 mb-1" style="font-size: clamp(1.8rem, 4.5vw, 2.8rem);">DOKTER SPESIALIS KAMI</h2>
                <p class="text-secondary mb-0">Praktik spesialis dengan jam layanan teratur dan kuota pasien terkontrol.</p>
            </div>
            @auth
                <a href="{{ route('patient.doctors.index') }}" class="adievia-btn-pill py-2 px-3.5">
                    Semua Dokter <i class="bi bi-arrow-right ms-1"></i>
                </a>
            @endauth
        </div>

        <div class="row g-4">
            @forelse ($doctors as $index => $doctor)
                <div class="col-md-6 col-lg-3 reveal delay-{{ ($index + 1) * 100 }}">
                    <div class="jhs-card-adievia bg-white">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <x-avatar :name="$doctor->name" size="lg" />
                            <div>
                                <h4 class="h6 fw-bold text-slate-900 mb-1" style="font-size: 0.95rem;">{{ $doctor->name }}</h4>
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold" style="font-size: 0.72rem;">
                                    {{ $doctor->specialization }}
                                </span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-2 border-top small text-secondary">
                            <div><i class="bi bi-person-badge me-1"></i>{{ $doctor->doctor_code }}</div>
                            <span class="text-muted" style="font-size: 0.76rem;">Poliklinik Spesialis</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border text-center text-secondary py-4">Belum ada dokter terdaftar.</div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- Section: Fasilitas Rawat Inap (NO LAB & NO RADIOLOGI) -->
<section id="fasilitas" class="py-5 bg-white">
    <div class="container py-lg-4">
        <div class="text-center mb-5 reveal">
            <span class="badge border bg-white text-primary rounded-pill px-3 py-1.5 fw-bold small mb-2 shadow-xs" style="border-color: rgba(13, 110, 253, 0.3) !important;">
                SARANA MEDIS
            </span>
            <h2 class="jhs-caps fw-black text-slate-900 mt-2 mb-2" style="font-size: clamp(2rem, 5vw, 3.2rem);">FASILITAS PELAYANAN RAWAT INAP</h2>
            <p class="text-secondary mx-auto mb-0" style="max-width: 520px;">
                Sarana penunjang rawat inap yang terhubung langsung dengan sistem antrean dan kasir.
            </p>
        </div>

        <div class="row g-3 g-md-4">
            @foreach (config('jhs.landing.facilities') as $index => $facility)
                <div class="col-6 col-md-4 col-lg-3 reveal delay-{{ ($index % 4 + 1) * 100 }}">
                    <div class="jhs-facility-card">
                        <div class="facility-icon">
                            <i class="bi {{ $facility['icon'] }}"></i>
                        </div>
                        <p class="small fw-bold text-slate-900 mb-0">{{ $facility['name'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Section: 4 Langkah Singkat Alur Pasien -->
<section id="alur" class="py-5" style="background: #f8fafc;">
    <div class="container py-lg-4">
        <div class="text-center mb-5 reveal">
            <span class="badge border bg-white text-primary rounded-pill px-3 py-1.5 fw-bold small mb-2 shadow-xs" style="border-color: rgba(13, 110, 253, 0.3) !important;">
                ALUR PELAYANAN
            </span>
            <h2 class="jhs-caps fw-black text-slate-900 mt-2 mb-2" style="font-size: clamp(2rem, 5vw, 3.2rem);">4 LANGKAH SEDERHANA</h2>
            <p class="text-secondary mx-auto mb-0" style="max-width: 540px;">
                Alur kunjungan terstruktur tanpa antrean menumpuk di satu titik.
            </p>
        </div>

        <div class="row g-4">
            @foreach (config('jhs.landing.steps') as $index => $step)
                <div class="col-md-6 col-lg-3 reveal delay-{{ ($index + 1) * 100 }}">
                    <div class="jhs-card-adievia bg-white text-center">
                        <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white fw-bold"
                             style="width: 44px; height: 44px; font-size: 1.1rem; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);">
                            {{ $index + 1 }}
                        </div>
                        <h3 class="h6 fw-bold text-slate-900 mb-2">{{ $step['title'] }}</h3>
                        <p class="small text-secondary mb-0" style="line-height: 1.55;">{{ $step['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Section: Kontak & Lokasi -->
<section id="kontak" class="py-5 bg-white border-top">
    <div class="container py-lg-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6 reveal">
                <span class="badge border bg-white text-primary rounded-pill px-3 py-1.5 fw-bold small mb-2 shadow-xs" style="border-color: rgba(13, 110, 253, 0.3) !important;">
                    INFORMASI &amp; KONTAK
                </span>
                <h2 class="jhs-caps fw-black text-slate-900 mt-2 mb-3" style="font-size: clamp(1.8rem, 4.5vw, 2.8rem);">PUSAT BANTUAN RUMAH SAKIT</h2>
                <p class="text-secondary mb-4" style="line-height: 1.6;">
                    Hubungi tim administrasi dan loket resepsionis kami untuk informasi jadwal dokter spesialis, pendaftaran pasien baru, dan verifikasi antrean.
                </p>

                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="card-icon mt-0" style="width: 42px; height: 42px; border-radius: 12px; background: rgba(13, 110, 253, 0.08); color: #0d6efd; display: grid; place-items: center; flex-shrink: 0;">
                            <i class="bi bi-geo-alt fs-5"></i>
                        </div>
                        <div>
                            <div class="small text-secondary">Alamat Rumah Sakit</div>
                            <div class="fw-bold text-slate-900">{{ config('jhs.contact.address') }}</div>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="card-icon mt-0" style="width: 42px; height: 42px; border-radius: 12px; background: rgba(13, 110, 253, 0.08); color: #0d6efd; display: grid; place-items: center; flex-shrink: 0;">
                            <i class="bi bi-telephone fs-5"></i>
                        </div>
                        <div>
                            <div class="small text-secondary">Layanan Telepon</div>
                            <div class="fw-bold text-slate-900">{{ config('jhs.contact.phone') }}</div>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="card-icon mt-0" style="width: 42px; height: 42px; border-radius: 12px; background: rgba(13, 110, 253, 0.08); color: #0d6efd; display: grid; place-items: center; flex-shrink: 0;">
                            <i class="bi bi-clock fs-5"></i>
                        </div>
                        <div>
                            <div class="small text-secondary">Jam Pelayanan Rawat Inap</div>
                            <div class="fw-bold text-slate-900">{{ config('jhs.contact.hours') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 reveal delay-200">
                <div class="p-4 p-md-5 rounded-4 border" style="background: linear-gradient(135deg, #f8fafc 0%, #edf4fc 100%);">
                    <div class="d-inline-flex p-2.5 rounded-3 bg-primary text-white mb-3 shadow-xs">
                        <i class="bi bi-shield-check fs-4"></i>
                    </div>
                    <h3 class="h5 fw-bold text-slate-900 mb-2">Kesiapan &amp; Keamanan Data Pasien</h3>
                    <p class="text-secondary small mb-4" style="line-height: 1.6;">
                        Sistem Informasi Rumah Sakit JOSJIS menerapkan prinsip enkripsi password, kontrol akses ketat per profesi medis (*Role-Based Access Control*), dan audit log otomatis untuk melindungi privasi data rekam medis pasien.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('register') }}" class="adievia-btn-pill-blue">
                            Mulai Pendaftaran Pasien
                        </a>
                        <a href="{{ route('login') }}" class="adievia-btn-pill">
                            Masuk Petugas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="py-4 border-top bg-white text-secondary small">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('images/logo-icon.png') }}" alt="{{ config('jhs.name') }}" height="26">
            <span class="fw-bold text-dark">{{ config('jhs.name') }}</span>
            <span>&middot; {{ config('jhs.tagline') }}</span>
        </div>
        <div>
            &copy; {{ date('Y') }} {{ config('jhs.name') }}. All rights reserved.
        </div>
    </div>
</footer>

<!-- Floating Quick Action Button (Chat / Portal Trigger ala Adievia) -->
<a href="{{ route('login') }}" class="adievia-fab" title="Masuk / Ambil Antrean">
    <i class="bi bi-chat-dots-fill"></i>
</a>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/jhs.js') }}"></script>

<!-- Scroll Reveal & Dynamic Navbar Shadow Script (Adievia Style) -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Floating Navbar scroll effect
        const header = document.getElementById('site-header');
        window.addEventListener('scroll', function () {
            if (window.scrollY > 30) {
                header.classList.add('is-scrolled');
            } else {
                header.classList.remove('is-scrolled');
            }
        });

        // Scroll Reveal Observer
        const reveals = document.querySelectorAll('.reveal');
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                    }
                });
            }, {
                threshold: 0.12,
                rootMargin: '0px 0px -40px 0px'
            });

            reveals.forEach((el) => observer.observe(el));
        } else {
            // Fallback for older browsers
            reveals.forEach((el) => el.classList.add('is-visible'));
        }
    });
</script>
</body>
</html>
