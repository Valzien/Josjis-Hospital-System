<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('jhs.name') }} &middot; {{ config('jhs.tagline') }}</title>
    <meta name="description" content="{{ config('jhs.description') }}">

    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jhs.css') }}">
</head>
<body class="jhs-body jhs-landing">

<nav class="navbar navbar-expand-lg jhs-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('landing') }}">
            <span class="jhs-logo-mark">+</span>{{ config('jhs.name') }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#jhsNav"
                aria-controls="jhsNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="jhsNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                @foreach (config('jhs.landing.sections') as $anchor => $label)
                    <li class="nav-item"><a class="nav-link" href="#{{ $anchor }}">{{ $label }}</a></li>
                @endforeach
                <li class="nav-item ms-lg-2">
                    <a href="{{ route('login') }}" class="btn btn-outline-primary">Masuk</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('register') }}" class="btn btn-primary">Daftar Pasien</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

@include('partials.flash')

<header class="jhs-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge rounded-pill jhs-hero-badge mb-3">
                    <i class="bi bi-shield-check me-1"></i> Terakreditasi Kemenkes &middot; Standar Keselamatan Pasien
                </span>
                <h1 class="display-4 fw-bold mb-3">{{ config('jhs.hero.title') }}</h1>
                <p class="lead text-secondary mb-4">{{ config('jhs.hero.subtitle') }}</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4">
                        <i class="bi bi-calendar-plus me-2"></i>Buat Akun & Ambil Antrean
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk Petugas
                    </a>
                </div>

                <div class="row g-3 mt-4 jhs-hero-stats">
                    @foreach ($stats as $stat)
                        <div class="col-4">
                            <div class="fs-3 fw-bold text-primary">{{ $stat['value'] }}</div>
                            <div class="small text-secondary">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-5">
                <div class="jhs-hero-card">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Layanan Hari Ini</h2>
                    <ul class="list-unstyled mb-0">
                        @forelse ($todaySchedules as $schedule)
                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <div class="fw-semibold">{{ $schedule->doctor->name }}</div>
                                    <div class="small text-secondary">{{ $schedule->doctor->specialization }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="small fw-semibold">{{ $schedule->timeRange() }}</div>
                                    <div class="small text-secondary">{{ $schedule->room ?? '-' }}</div>
                                </div>
                            </li>
                        @empty
                            <li class="text-secondary small py-3 mb-0">Belum ada jadwal praktik hari ini.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

<section id="layanan" class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Layanan Unggulan</h2>
            <p class="text-secondary">Satu sistem untuk seluruh alur pelayanan pasien</p>
        </div>

        <div class="row g-4">
            @foreach (config('jhs.landing.features') as $feature)
                <div class="col-md-6 col-lg-4">
                    <div class="jhs-feature h-100">
                        <span class="jhs-feature-icon"><i class="bi {{ $feature['icon'] }}"></i></span>
                        <h3 class="h6 fw-bold mt-3">{{ $feature['title'] }}</h3>
                        <p class="small text-secondary mb-0">{{ $feature['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="dokter" class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <h2 class="fw-bold mb-1">Dokter kami</h2>
                <p class="text-secondary mb-0">Pilih dokter sesuai kebutuhan Anda</p>
            </div>
            @auth
                <a href="{{ route('patient.doctors.index') }}" class="btn btn-sm btn-outline-primary">
                    Lihat semua dokter <i class="bi bi-arrow-right ms-1"></i>
                </a>
            @endauth
        </div>

        <div class="row g-4">
            @forelse ($doctors as $doctor)
                <div class="col-md-6 col-lg-4">
                    <div class="jhs-doctor-card h-100">
                        <div class="d-flex align-items-center gap-3">
                            <x-avatar :name="$doctor->name" size="lg" />
                            <div>
                                <h3 class="h6 fw-bold mb-1">{{ $doctor->name }}</h3>
                                <span class="badge jhs-soft-badge">{{ $doctor->specialization }}</span>
                            </div>
                        </div>

                        <div class="small text-secondary mt-3">
                            <i class="bi bi-person-badge me-1"></i>{{ $doctor->doctor_code }}
                        </div>
                        @if ($doctor->isOnDuty())
                            <div class="small text-success mt-1">
                                <i class="bi bi-circle-fill me-1" style="font-size:.5rem"></i> Sedang melayani pasien
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border text-center">Belum ada dokter terdaftar.</div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<section id="fasilitas" class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Fasilitas</h2>
            <p class="text-secondary">Sarana penunjang untuk pasien dan petugas</p>
        </div>
        <div class="row g-4">
            @foreach (config('jhs.landing.facilities') as $facility)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="text-center p-3">
                        <i class="bi {{ $facility['icon'] }} fs-2 text-primary"></i>
                        <p class="small fw-semibold mt-2 mb-0">{{ $facility['name'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="alur" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Alur Pelayanan</h2>
            <p class="text-secondary">Empat langkah sederhana, tanpa antre panjang</p>
        </div>
        <div class="row g-4">
            @foreach (config('jhs.landing.steps') as $index => $step)
                <div class="col-md-6 col-lg-3">
                    <div class="jhs-step h-100 text-center">
                        <span class="jhs-step-number">{{ $index + 1 }}</span>
                        <h3 class="h6 fw-bold mt-3">{{ $step['title'] }}</h3>
                        <p class="small text-secondary mb-0">{{ $step['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="kontak" class="py-5 bg-white">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-3">Hubungi Kami</h2>
                <p class="text-secondary">{{ config('jhs.description') }}</p>

                <ul class="list-unstyled jhs-contact-list">
                    <li class="d-flex gap-3 mb-3">
                        <i class="bi bi-geo-alt text-primary"></i>
                        <span>{{ config('jhs.contact.address') }}</span>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <i class="bi bi-telephone text-primary"></i>
                        <span>{{ config('jhs.contact.phone') }}</span>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <i class="bi bi-envelope text-primary"></i>
                        <span>{{ config('jhs.contact.email') }}</span>
                    </li>
                    <li class="d-flex gap-3">
                        <i class="bi bi-clock text-primary"></i>
                        <span>{{ config('jhs.contact.hours') }}</span>
                    </li>
                </ul>
            </div>

            <div class="col-lg-6">
                <div class="jhs-hero-card">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Layanan Darurat 24 Jam</h2>
                    <p class="small text-secondary">
                        IGDuty buka 24 jam setiap hari. Pasien darurat tidak memerlukan antrean online dan langsung
                        dilayani oleh tim gawat darurat.
                    </p>
                    <hr>
                    <p class="small text-secondary mb-3">Pasien dapat mengambil antrean secara online melalui portal pasien.</p>
                    <a href="{{ route('register') }}" class="btn btn-primary w-100">Daftar Sekarang</a>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="jhs-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 text-white fw-bold mb-3">
                    <span class="jhs-logo-mark">+</span>{{ config('jhs.name') }}
                </div>
                <p class="small text-white-50 mb-0">{{ config('jhs.description') }}</p>
            </div>
            <div class="col-6 col-lg-2">
                <h3 class="h6 text-white">Navigasi</h3>
                <ul class="list-unstyled small text-white-50">
                    @foreach (config('jhs.landing.sections') as $anchor => $label)
                        <li class="mb-2"><a href="#{{ $anchor }}" class="text-white-50 text-decoration-none">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h3 class="h6 text-white">Kontak</h3>
                <ul class="list-unstyled small text-white-50">
                    <li class="mb-2">{{ config('jhs.contact.address') }}</li>
                    <li class="mb-2">{{ config('jhs.contact.phone') }}</li>
                    <li>{{ config('jhs.contact.email') }}</li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h3 class="h6 text-white">Ikuti Kami</h3>
                <div class="d-flex gap-2">
                    @foreach (['facebook', 'instagram', 'youtube'] as $social)
                        <a href="#" class="btn btn-sm btn-outline-light" aria-label="{{ $social }}"><i class="bi bi-{{ $social }}"></i></a>
                    @endforeach
                </div>
            </div>
        </div>

        <hr class="border-light opacity-25">

        <p class="small text-white-50 mb-0 text-center">
            <i class="bi bi-copyright"></i> {{ date('Y') }} {{ config('jhs.name') }}. Seluruh hak cipta dilindungi.
        </p>
    </div>
</footer>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/jhs.js') }}"></script>
</body>
</html>