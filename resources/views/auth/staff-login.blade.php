@extends('layouts.guest')

@section('title', $portalConfig['title'])

@section('content')
    <div class="text-center mb-4">
        <div class="d-inline-flex p-2 rounded-4 bg-primary bg-opacity-10 mb-3 shadow-xs">
            <span class="jhs-stat-icon jhs-icon-soft-primary mb-0" style="width:54px;height:54px; background: rgba(13, 110, 253, 0.1); color: #0d6efd; border-radius: 14px; display: grid; place-items: center;">
                <i class="bi {{ $portalConfig['icon'] }} fs-3"></i>
            </span>
        </div>
        <h1 class="h4 fw-bold text-slate-900 mb-1">{{ $portalConfig['title'] }}</h1>
        <p class="text-secondary small mb-0">{{ $portalConfig['subtitle'] }}</p>
    </div>

    <!-- Quick Role Switcher Pills (Adievia Style) -->
    <div class="p-1.5 bg-light rounded-pill border d-flex justify-content-between mb-4">
        <a href="{{ route('login') }}" class="jhs-role-pill flex-grow-1 text-center justify-content-center">
            <i class="bi bi-person me-1"></i>Portal Pasien
        </a>
        <span class="jhs-role-pill active flex-grow-1 text-center justify-content-center">
            <i class="bi {{ $portalConfig['icon'] }} me-1"></i>{{ $portalConfig['role']->shortLabel() }}
        </span>
    </div>

    <form method="POST" action="{{ url($portal.'/login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label small fw-bold text-slate-800">Email Petugas ({{ $portalConfig['role']->label() }})</label>
            <div class="jhs-input-group @error('email') is-invalid @enderror">
                <span class="jhs-input-group-icon"><i class="bi bi-envelope text-primary"></i></span>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="form-control"
                       placeholder="{{ $portal }}@josjis.test" required autofocus autocomplete="username">
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
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill small">
                <i class="bi {{ $portalConfig['icon'] }} me-1"></i>{{ $portalConfig['role']->label() }}
            </span>
        </div>

        <button type="submit" class="adievia-btn-pill w-100 py-2.5 shadow-xs justify-content-center text-center jhs-btn-interactive border-0" style="font-size: 0.92rem;">
            <i class="bi bi-box-arrow-in-right me-2 jhs-btn-icon"></i>Masuk sebagai {{ $portalConfig['role']->label() }}
        </button>
    </form>

    @if ($demoAccount)
        <div class="jhs-demo-box jhs-demo-credentials mt-4 rounded-3 p-3 border">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-bold text-dark">
                    <i class="bi bi-person-badge text-primary me-1"></i>Akun Demo {{ $portalConfig['role']->label() }}
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.68rem;">Klik untuk Isi Otomatis</span>
            </div>
            <button type="button" class="adievia-btn-pill-outline w-100 js-fill-demo py-2 jhs-btn-interactive" style="font-size: 0.8rem;"
                    data-email="{{ $demoAccount['email'] }}" data-password="{{ $demoAccount['password'] }}">
                <i class="bi bi-magic me-1.5 jhs-btn-icon"></i>
                <span>Isi {{ $demoAccount['email'] }}</span>
            </button>
        </div>
    @endif

    <div class="text-center mt-4 pt-3 border-top">
        <p class="small text-secondary mb-2 fw-medium">Ganti Portal Petugas Lain:</p>
        <div class="d-flex flex-wrap justify-content-center gap-1.5">
            <a href="{{ route('login') }}" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 text-decoration-none small jhs-btn-interactive text-secondary">
                <i class="bi bi-person me-1 text-primary"></i>Pasien
            </a>
            @foreach (config('jhs.staff_portals') as $key => $item)
                @continue($key === $portal)
                <a href="{{ route($key.'.login') }}" class="btn btn-sm btn-light border rounded-pill px-2.5 py-1 text-decoration-none small jhs-btn-interactive text-secondary">
                    <i class="bi {{ $item['icon'] }} me-1 text-primary"></i>{{ $item['role']->shortLabel() }}
                </a>
            @endforeach
        </div>
    </div>
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
            }
        });
    });
</script>
@endpush
