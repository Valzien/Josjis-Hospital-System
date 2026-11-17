@extends('layouts.guest')

@section('title', 'Masuk Layanan Pasien')

@section('content')
    <div class="text-center mb-3">
        <div class="d-inline-flex p-2 rounded-4 bg-primary bg-opacity-10 mb-3 shadow-xs">
            <img src="{{ asset('images/logo-icon.png') }}" alt="{{ config('jhs.name') }}" height="52" class="d-inline-block">
        </div>
        <h1 class="h4 fw-bold text-slate-900 mb-1" style="font-family: var(--jhs-font-display);">Masuk Layanan Pasien</h1>
        <p class="text-secondary small mb-0">Masuk untuk mengambil nomor antrean, memantau giliran panggilan, dan riwayat resep</p>
    </div>

    <!-- Banner Khusus Pasien yang Belum Terdaftar -->
    <div class="p-3 mb-4 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2.5">
            <div class="rounded-circle bg-white text-primary shadow-xs d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                <i class="bi bi-person-plus-fill fs-5"></i>
            </div>
            <div>
                <div class="fw-bold text-slate-900 small">Belum Terdaftar sebagai Pasien?</div>
                <div class="text-secondary small" style="font-size: 0.76rem;">Buat akun baru mandiri untuk mengambil nomor antrean</div>
            </div>
        </div>
        <a href="{{ route('register') }}" class="adievia-btn-pill py-1.5 px-3.5 text-nowrap ms-2 jhs-btn-interactive" style="font-size: 0.78rem;">
            <span>Daftar</span>
            <i class="bi bi-arrow-right ms-1 jhs-btn-arrow"></i>
        </a>
    </div>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label small fw-bold text-slate-800">Email Pasien</label>
            <div class="jhs-input-group @error('email') is-invalid @enderror">
                <span class="jhs-input-group-icon"><i class="bi bi-envelope text-primary"></i></span>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="form-control"
                       placeholder="nama@email.com" required autofocus autocomplete="username">
            </div>
            @error('email')
                <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="password" class="form-label small fw-bold text-slate-800 mb-0">Kata Sandi</label>
                <a href="{{ route('password.request') }}" class="small text-primary text-decoration-none fw-medium" tabindex="-1">Lupa sandi?</a>
            </div>
            <div class="jhs-input-group @error('password') is-invalid @enderror">
                <span class="jhs-input-group-icon"><i class="bi bi-lock text-primary"></i></span>
                <input type="password" name="password" id="password"
                       class="form-control"
                       placeholder="Masukkan kata sandi" required autocomplete="current-password">
                <button class="jhs-password-toggle" type="button" id="togglePassword" title="Lihat/Sembunyikan sandi" tabindex="-1">
                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                </button>
            </div>
            @error('password')
                <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label small text-secondary" for="remember">Ingat sesi login</label>
            </div>
        </div>

        <button type="submit" class="adievia-btn-pill w-100 py-2.5 shadow-xs justify-content-center text-center jhs-btn-interactive border-0" style="font-size: 0.92rem;">
            <i class="bi bi-box-arrow-in-right me-2 jhs-btn-icon"></i>Masuk sebagai Pasien
        </button>
    </form>

    <div class="text-center mt-3 pt-3 border-top">
        <p class="small text-secondary mb-0">
            Belum terdaftar sebagai pasien? 
            <a href="{{ route('register') }}" class="fw-bold text-primary text-decoration-none">Daftar di Sini &rarr;</a>
        </p>
    </div>

    <!-- Staff Login Link at Bottom -->
    <div class="text-center mt-3 pt-2">
        <a href="{{ route('admin.login') }}" class="text-secondary small text-decoration-none" style="font-size: 0.78rem;">
            <i class="bi bi-shield-lock me-1 text-primary"></i>Petugas atau Tenaga Medis? Masuk di Sini
        </a>
    </div>

    <!-- Staff Portal Jump Section -->
    <div class="text-center mt-4 pt-3 border-top">
        <p class="small text-secondary mb-2 fw-medium">
            <i class="bi bi-hospital me-1 text-primary"></i>Pintu Masuk Petugas Rumah Sakit:
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-1.5">
            @foreach (config('jhs.staff_portals') as $key => $item)
                <a href="{{ route($key.'.login') }}" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 text-decoration-none small jhs-btn-interactive text-secondary">
                    <i class="bi {{ $item['icon'] }} me-1 text-primary"></i>{{ $item['role']->shortLabel() }}
                </a>
            @endforeach
        </div>
    </div>

    @if (config('jhs.demo_credentials_enabled') && ($patientDemo = config('jhs.demo_accounts.Pasien')))
        <div class="jhs-demo-box jhs-demo-credentials mt-4 rounded-3 p-3 border">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-bold text-dark">
                    <i class="bi bi-person-badge text-primary me-1"></i>Akun Demo Pasien
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.68rem;">Klik untuk Isi Otomatis</span>
            </div>
            <p class="text-secondary small mb-2" style="font-size: 0.76rem;">Gunakan kredensial contoh pasien untuk demonstrasi:</p>
            <button type="button" class="adievia-btn-pill-outline w-100 js-fill-demo py-2 jhs-btn-interactive" style="font-size: 0.8rem;"
                    data-email="{{ $patientDemo['email'] }}" data-password="{{ $patientDemo['password'] }}">
                <i class="bi bi-magic me-1.5 jhs-btn-icon"></i>
                <span>Isi {{ $patientDemo['email'] }}</span>
            </button>
            <div id="demoSelectedHint" class="small text-primary mt-2 d-none text-center" style="font-size: 0.74rem;">
                <i class="bi bi-check-circle-fill me-1"></i>Akun pasien terisi otomatis.
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    const toggleBtn = document.getElementById('togglePassword');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    const pwdInput = document.getElementById('password');

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = pwdInput.type === 'password';
            pwdInput.type = isPassword ? 'text' : 'password';
            toggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }

    document.querySelectorAll('.js-fill-demo').forEach(btn => {
        btn.addEventListener('click', function () {
            const emailInput = document.getElementById('email');
            const pwdInput = document.getElementById('password');
            if (emailInput && pwdInput) {
                emailInput.value = this.dataset.email || '';
                pwdInput.value = this.dataset.password || '';
                const hint = document.getElementById('demoSelectedHint');
                if (hint) hint.classList.remove('d-none');
            }
        });
    });
</script>
@endpush
