<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('jhs.name'))</title>

    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jhs.css') }}">
    @stack('styles')
</head>
<body class="jhs-body @yield('body-class')">

<div class="jhs-auth-shell">
    <aside class="jhs-auth-aside d-none d-lg-flex">
        <div class="jhs-auth-aside-inner">
            <a href="{{ route('landing') }}" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none mb-5">
                <span class="jhs-logo-mark">+</span>
                <span class="fs-4 fw-bold">{{ config('jhs.name') }}</span>
            </a>

            <p class="text-white-50 mb-4">{{ config('jhs.tagline') }}</p>

            <ul class="list-unstyled jhs-auth-points mb-5">
                @foreach ([
                    ['bi-calendar-check', 'Antrean digital tanpa antre panjang'],
                    ['bi-stethoscope', 'Rekam medis & resep elektronik'],
                    ['bi-capsule', 'Stok obat terpantau real-time'],
                    ['bi-graph-up-arrow', 'Laporan manajemen instan'],
                ] as [$icon, $text])
                    <li class="d-flex align-items-start gap-3 mb-3">
                        <i class="bi {{ $icon }} fs-5"></i>
                        <span class="text-white">{{ $text }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-auto small text-white-50">
                <i class="bi bi-telephone me-2"></i>{{ config('jhs.contact.phone') }}
                <span class="d-block mt-1"><i class="bi bi-geo-alt me-2"></i>{{ config('jhs.contact.address') }}</span>
            </div>
        </div>
    </aside>

    <main class="jhs-auth-main">
        <div class="jhs-auth-card">
            @include('partials.flash')

            @yield('content')
        </div>

        <p class="text-center text-secondary small mt-4 mb-0">
            <i class="bi bi-copyright"></i> {{ date('Y') }} {{ config('jhs.name') }} &middot; Sistem Informasi Rumah Sakit
        </p>
    </main>
</div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/jhs.js') }}"></script>
@stack('scripts')
</body>
</html>