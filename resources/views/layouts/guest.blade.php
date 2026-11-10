<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('jhs.name'))</title>

    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jhs.css').'?v='.@filemtime(public_path('css/jhs.css')) }}">
    @stack('styles')
</head>
<body class="jhs-body @yield('body-class')">

<div class="jhs-auth-shell">
    <aside class="jhs-auth-aside d-none d-lg-flex">
        <div class="jhs-auth-aside-inner">
            <a href="{{ route('landing') }}" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none mb-4">
                <img src="{{ asset('images/logo-icon.png') }}" alt="{{ config('jhs.name') }}" height="42" class="bg-white p-1 rounded-3 shadow-sm">
                <span class="fs-4 fw-bold">{{ config('jhs.name') }}</span>
            </a>

            <p class="text-white-50 mb-5" style="line-height: 1.6;">{{ config('jhs.description') }}</p>

            <ul class="list-unstyled jhs-auth-points mb-5">
                @foreach ([
                    ['bi-calendar-check', 'Antrean Digital Cepat', 'Pendaftaran antrean mandiri secara real-time per dokter.'],
                    ['bi-file-earmark-medical', 'Rekam Medis Elektronik', 'Pencatatan klinis, diagnosis, dan tindakan terstandar.'],
                    ['bi-capsule', 'Integrasi Resep & Farmasi', 'Verifikasi stok obat otomatis saat penyerahan resep.'],
                    ['bi-shield-check', 'Aman & Terverifikasi', 'Audit trail aktivitas dan kontrol akses berbasis peran.'],
                ] as [$icon, $title, $desc])
                    <li class="d-flex align-items-start gap-3 mb-4">
                        <span class="jhs-auth-point-icon">
                            <i class="bi {{ $icon }}"></i>
                        </span>
                        <div>
                            <div class="fw-semibold text-white" style="font-size: 0.95rem;">{{ $title }}</div>
                            <div class="text-white-50 small mt-1" style="line-height: 1.4;">{{ $desc }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-auto pt-4 border-top border-white border-opacity-10 small text-white-50">
                <div><i class="bi bi-telephone me-2 text-white-50"></i>{{ config('jhs.contact.phone') }}</div>
                <div class="mt-1"><i class="bi bi-geo-alt me-2 text-white-50"></i>{{ config('jhs.contact.address') }}</div>
            </div>
        </div>
    </aside>

    <main class="jhs-auth-main">
        <div class="w-100 d-flex justify-content-between align-items-center mb-3" style="max-width: 450px;">
            <a href="{{ route('landing') }}" class="btn btn-sm btn-white border shadow-xs rounded-pill px-3 py-1.5 text-secondary d-inline-flex align-items-center gap-2 text-decoration-none">
                <i class="bi bi-arrow-left text-primary"></i>
                <span class="fw-semibold small">Kembali ke Beranda</span>
            </a>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1.5 rounded-pill small">
                <i class="bi bi-hospital me-1"></i>Portal Pelayanan
            </span>
        </div>

        <div class="jhs-auth-card @yield('card-class')">
            @include('partials.flash')

            @yield('content')
        </div>

        <p class="text-center text-secondary small mt-4 mb-0">
            <i class="bi bi-copyright"></i> {{ date('Y') }} {{ config('jhs.name') }} &middot; Layanan Kesehatan Terpadu
        </p>
    </main>
</div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/jhs.js') }}"></script>
@stack('scripts')
</body>
</html>