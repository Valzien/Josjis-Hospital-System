<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('jhs.name') }} &middot; {{ config('jhs.tagline') }}</title>
    <meta name="description" content="{{ config('jhs.description') }}">

    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jhs.css') }}">
</head>
<body class="jhs-body jhs-landing">

<nav class="navbar navbar-expand-lg jhs-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('landing') }}">
            <img src="{{ asset('images/logo-icon.png') }}" alt="{{ config('jhs.name') }}" height="36" class="d-inline-block">
            <span>{{ config('jhs.name') }}</span>
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

<header class="jhs-hero text-center py-5">
    <div class="container py-lg-4">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">
                <span class="badge rounded-pill jhs-hero-badge mb-3">
                    <i class="bi bi-shield-check me-1 text-primary"></i> Terakreditasi Kemenkes &middot; Standar Keselamatan Pasien
                </span>
                <h1 class="display-4 fw-bold mb-3" style="line-height:1.25">
                    Pelayanan Rumah Sakit<br>
                    <span style="background:linear-gradient(90deg,var(--jhs-primary),var(--jhs-teal));-webkit-background-clip:text;-webkit-text-fill-color:transparent">Cepat, Akurat &amp; Terpadu</span>
                </h1>
                <p class="lead text-secondary mb-4 mx-auto" style="font-size:1.15rem; max-width:640px">
                    {{ config('jhs.description') }}
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4 shadow-sm">
                        <i class="bi bi-calendar-plus me-2"></i>Buat Akun &amp; Ambil Antrean
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk Petugas
                    </a>
                </div>

                <div class="d-flex flex-wrap justify-content-center align-items-center gap-4 text-secondary small pt-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Antrean Online Real-Time</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Rekam Medis Terintegrasi</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Farmasi &amp; Resep Digital</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<section id="layanan" class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <span class="jhs-soft-badge mb-2 d-inline-block"><i class="bi bi-stars me-1"></i>Layanan</span>
            <h2 class="fw-bold mt-2">Layanan Unggulan</h2>
            <p class="text-secondary mx-auto" style="max-width:520px">Satu sistem terpadu untuk seluruh alur pelayanan pasien, dari registrasi hingga penyerahan obat</p>
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

<section id="dokter" class="py-5" style="background:#f8fafc">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <span class="jhs-soft-badge mb-2 d-inline-block"><i class="bi bi-heart-pulse me-1"></i>Tim Medis</span>
                <h2 class="fw-bold mb-1 mt-2">Dokter Kami</h2>
                <p class="text-secondary mb-0">Pilih dokter spesialis sesuai kebutuhan Anda</p>
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
                                <span class="jhs-soft-badge">{{ $doctor->specialization }}</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <div class="small text-secondary">
                                <i class="bi bi-person-badge me-1"></i>{{ $doctor->doctor_code }}
                            </div>
                            @if ($doctor->isOnDuty())
                                <span class="d-inline-flex align-items-center gap-1 small text-success fw-semibold">
                                    <i class="bi bi-circle-fill" style="font-size:.45rem"></i> Sedang bertugas
                                </span>
                            @endif
                        </div>
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
            <span class="jhs-soft-badge mb-2 d-inline-block"><i class="bi bi-building me-1"></i>Sarana</span>
            <h2 class="fw-bold mt-2">Fasilitas</h2>
            <p class="text-secondary">Sarana penunjang lengkap untuk pasien dan petugas</p>
        </div>
        <div class="row g-4">
            @foreach (config('jhs.landing.facilities') as $facility)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="text-center p-3 rounded-3" style="border:1px solid var(--jhs-border);transition:all .18s ease;cursor:default"
                         onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(16,27,45,.08)'"
                         onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                        <div class="jhs-feature-icon mx-auto" style="background:var(--jhs-primary-light);color:var(--jhs-primary-dark)">
                            <i class="bi {{ $facility['icon'] }}"></i>
                        </div>
                        <p class="small fw-semibold mt-2 mb-0">{{ $facility['name'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="alur" class="py-5" style="background:#f8fafc">
    <div class="container">
        <div class="text-center mb-5">
            <span class="jhs-soft-badge mb-2 d-inline-block"><i class="bi bi-signpost-split me-1"></i>Alur</span>
            <h2 class="fw-bold mt-2">Alur Pelayanan</h2>
            <p class="text-secondary">Empat langkah sederhana, tanpa antre panjang</p>
        </div>
        <div class="row g-4">
            @foreach (config('jhs.landing.steps') as $index => $step)
                <div class="col-md-6 col-lg-3">
                    <div class="jhs-step h-100 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center mx-auto">
                            <span class="jhs-step-number">{{ $index + 1 }}</span>
                        </div>
                        <h3 class="h6 fw-bold mt-3">{{ $step['title'] }}</h3>
                        <p class="small text-secondary mb-0">{{ $step['text'] }}</p>
                    </div>
                </div>
                @if (!$loop->last)
                    {{-- Connector arrow (visible on lg+) --}}
                @endif
            @endforeach
        </div>
    </div>
</section>

<section id="kontak" class="py-5 bg-white">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-lg-6">
                <span class="jhs-soft-badge mb-2 d-inline-block"><i class="bi bi-telephone me-1"></i>Kontak</span>
                <h2 class="fw-bold mb-3 mt-2">Hubungi Kami</h2>
                <p class="text-secondary">{{ config('jhs.description') }}</p>

                <ul class="list-unstyled jhs-contact-list mt-4">
                    <li class="d-flex gap-3 mb-3">
                        <div class="jhs-feature-icon" style="width:40px;height:40px;border-radius:11px;font-size:1rem;margin-bottom:0;background:var(--jhs-primary-light);color:var(--jhs-primary-dark);flex-shrink:0">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div>
                            <div class="small text-secondary">Alamat</div>
                            <div class="fw-semibold" style="font-size:.88rem">{{ config('jhs.contact.address') }}</div>
                        </div>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <div class="jhs-feature-icon" style="width:40px;height:40px;border-radius:11px;font-size:1rem;margin-bottom:0;background:var(--jhs-primary-light);color:var(--jhs-primary-dark);flex-shrink:0">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div>
                            <div class="small text-secondary">Telepon</div>
                            <div class="fw-semibold" style="font-size:.88rem">{{ config('jhs.contact.phone') }}</div>
                        </div>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <div class="jhs-feature-icon" style="width:40px;height:40px;border-radius:11px;font-size:1rem;margin-bottom:0;background:var(--jhs-primary-light);color:var(--jhs-primary-dark);flex-shrink:0">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div>
                            <div class="small text-secondary">Email</div>
                            <div class="fw-semibold" style="font-size:.88rem">{{ config('jhs.contact.email') }}</div>
                        </div>
                    </li>
                    <li class="d-flex gap-3">
                        <div class="jhs-feature-icon" style="width:40px;height:40px;border-radius:11px;font-size:1rem;margin-bottom:0;background:var(--jhs-primary-light);color:var(--jhs-primary-dark);flex-shrink:0">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div>
                            <div class="small text-secondary">Jam Operasional</div>
                            <div class="fw-semibold" style="font-size:.88rem">{{ config('jhs.contact.hours') }}</div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="col-lg-6">
                <div class="jhs-hero-card" style="background:linear-gradient(135deg,#f0f7ff 0%,#e8faf8 100%);border-color:var(--jhs-border)">
                    <div class="text-center mb-3">
                        <div class="jhs-feature-icon mx-auto" style="background:linear-gradient(135deg,var(--jhs-primary),var(--jhs-teal));color:#fff;width:56px;height:56px;font-size:1.5rem;border-radius:16px">
                            <i class="bi bi-hospital"></i>
                        </div>
                    </div>
                    <h2 class="h6 fw-bold mb-2 text-center">Layanan Darurat 24 Jam</h2>
                    <p class="small text-secondary text-center">
                        IGDuty buka 24 jam setiap hari. Pasien darurat tidak memerlukan antrean online dan langsung
                        dilayani oleh tim gawat darurat.
                    </p>
                    <hr>
                    <p class="small text-secondary mb-3 text-center">Pasien dapat mengambil antrean secara online melalui portal pasien.</p>
                    <a href="{{ route('register') }}" class="btn btn-primary w-100">
                        <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
                    </a>
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
                    <img src="{{ asset('images/logo-icon.png') }}" alt="{{ config('jhs.name') }}" height="32" class="d-inline-block bg-white p-1 rounded-2 shadow-sm">
                    <span>{{ config('jhs.name') }}</span>
                </div>
                <p class="small text-white-50 mb-0" style="line-height:1.7">{{ config('jhs.description') }}</p>
            </div>
            <div class="col-6 col-lg-2">
                <h3 class="h6 text-white">Navigasi</h3>
                <ul class="list-unstyled small">
                    @foreach (config('jhs.landing.sections') as $anchor => $label)
                        <li class="mb-2"><a href="#{{ $anchor }}" class="text-white-50 text-decoration-none" style="transition:color .15s" onmouseover="this.style.color='rgba(255,255,255,0.85)'" onmouseout="this.style.color=''">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h3 class="h6 text-white">Kontak</h3>
                <ul class="list-unstyled small text-white-50">
                    <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>{{ config('jhs.contact.address') }}</li>
                    <li class="mb-2"><i class="bi bi-telephone me-2"></i>{{ config('jhs.contact.phone') }}</li>
                    <li><i class="bi bi-envelope me-2"></i>{{ config('jhs.contact.email') }}</li>
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

        <hr class="border-light opacity-25 my-4">

        <p class="small text-white-50 mb-0 text-center">
            <i class="bi bi-copyright"></i> {{ date('Y') }} {{ config('jhs.name') }}. Seluruh hak cipta dilindungi.
        </p>
    </div>
</footer>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/jhs.js') }}"></script>
</body>
</html>