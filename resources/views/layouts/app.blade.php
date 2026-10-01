<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') &middot; {{ $jhsConfig['short_name'] }}</title>

    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jhs.css') }}">
    @stack('styles')
    @if(config('app.env') === 'local')
        <script>
            window.JHS_DEBUG = true;
        </script>
    @endif
</head>
<body>
    @include('partials.sidebar')

    <div class="jhs-main">
        @include('partials.topbar')

        <main class="jhs-content">
            @if (session('impersonate_warning'))
                <div class="alert alert-warning d-flex align-items-center gap-2 jhs-no-print">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div class="flex-grow-1">{{ session('impersonate_warning') }}</div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @yield('modals')

    @include('partials.flash')

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/apexcharts/js/apexcharts.min.js') }}"></script>
    <script src="{{ asset('js/jhs.js') }}"></script>
    @stack('scripts')
</body>
</html>
