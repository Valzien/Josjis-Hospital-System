<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('jhs.name'))</title>

    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Inter+Tight:ital,wght@0,300..900;1,300..900&family=Parkinsans:wght@300..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jhs.css').'?v='.@filemtime(public_path('css/jhs.css')) }}">
    @stack('styles')
</head>
<body class="jhs-body @yield('body-class')" style="background-color: #f8fafc;">

<div class="jhs-auth-shell">
    <!-- Left Aside (Adievia Style with White & Blue Theme) -->
    <aside class="jhs-auth-aside d-none d-lg-flex position-relative overflow-hidden" 
           style="background: linear-gradient(150deg, #0d6efd 0%, #1e40af 100%);">
        
        <!-- ECG Heartbeat SVG Wave Animation -->
        <svg class="jhs-ecg-canvas" viewBox="0 0 1200 100" preserveAspectRatio="none" style="top: 25%; opacity: 0.35;">
            <path class="jhs-ecg-path" fill="none" stroke="#ffffff" stroke-width="2.5"
                  d="M0,50 L250,50 L270,50 L280,20 L290,85 L300,10 L310,70 L320,50 L340,50 L580,50 L600,50 L610,20 L620,85 L630,10 L640,70 L650,50 L670,50 L910,50 L930,50 L940,20 L950,85 L960,10 L970,70 L980,50 L1000,50 L1200,50" />
        </svg>

        <div class="jhs-auth-aside-inner position-relative" style="z-index: 2;">
            <div class="mb-4">
                <a href="{{ route('landing') }}" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none">
                    <img src="{{ asset('images/logo-icon.png') }}" alt="{{ config('jhs.name') }}" height="42" class="bg-white p-1 rounded-3 shadow-sm">
                    <span class="fs-4 fw-bold tracking-tight">{{ config('jhs.name') }}</span>
                </a>
            </div>

            <p class="text-white text-opacity-90 mb-5" style="line-height: 1.6; font-size: 0.95rem;">
                Platform terpadu untuk percepatan alur antrean, rekam medis digital, pengelolaan e-resep farmasi, dan administrasi billing rumah sakit.
            </p>

            <ul class="list-unstyled jhs-auth-points mb-5">
                @foreach ([
                    ['bi-calendar-check', 'Antrean Poliklinik Real-Time', 'Pengambilan antrean mandiri berbasis kuota dan jam praktik WIB.'],
                    ['bi-file-earmark-medical', 'Rekam Medis Elektronik (RME)', 'Pencatatan anamnesis, vital signs, diagnosis, dan tindakan terstandar.'],
                    ['bi-capsule', 'Integrasi Resep & Farmasi', 'Validasi resep digital langsung dengan pemotongan stok obat otomatis.'],
                    ['bi-cash-coin', 'Kasir & Kuitansi Terpadu', 'Transparansi billing dan pencetakan bukti pembayaran resmi.'],
                ] as [$icon, $title, $desc])
                    <li class="d-flex align-items-start gap-3 mb-4">
                        <span class="jhs-auth-point-icon" style="background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.25);">
                            <i class="bi {{ $icon }}"></i>
                        </span>
                        <div>
                            <div class="fw-semibold text-white" style="font-size: 0.95rem;">{{ $title }}</div>
                            <div class="text-white text-opacity-75 small mt-1" style="line-height: 1.4;">{{ $desc }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-auto pt-4 border-top border-white border-opacity-15 small text-white text-opacity-75">
                <div><i class="bi bi-clock me-2"></i>{{ config('jhs.contact.hours') }}</div>
                <div class="mt-1"><i class="bi bi-geo-alt me-2"></i>{{ config('jhs.contact.address') }}</div>
            </div>
        </div>
    </aside>

    <!-- Right Content (Login Card) -->
    <main class="jhs-auth-main">
        <div class="w-100 d-flex justify-content-between align-items-center mb-3" style="max-width: 460px;">
            <a href="{{ route('landing') }}" class="btn btn-sm btn-white border shadow-xs rounded-pill px-3 py-1.5 text-secondary d-inline-flex align-items-center gap-2 text-decoration-none jhs-btn-interactive">
                <i class="bi bi-arrow-left text-primary jhs-btn-icon"></i>
                <span class="fw-semibold small">Kembali ke Beranda</span>
            </a>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1.5 rounded-pill small fw-semibold">
                <i class="bi bi-shield-check me-1"></i>Portal Resmi JHS
            </span>
        </div>

        <div class="jhs-auth-adievia-card @yield('card-class')" style="max-width: 460px; width: 100%;">
            @include('partials.flash')

            @yield('content')
        </div>

        <p class="text-center text-secondary small mt-4 mb-0">
            &copy; {{ date('Y') }} {{ config('jhs.name') }} &middot; Layanan Rawat Jalan Terpadu
        </p>
    </main>
</div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/jhs.js') }}"></script>
@stack('scripts')
</body>
</html>
