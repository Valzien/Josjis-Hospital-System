@extends('layouts.guest')

@section('title', $portalConfig['title'])

@section('content')
    <div class="text-center mb-4">
        <div class="d-inline-flex p-2 rounded-4 bg-{{ $portalConfig['color'] }} bg-opacity-10 mb-3 shadow-xs">
            <span class="jhs-stat-icon jhs-icon-soft-{{ $portalConfig['color'] }} mb-0" style="width:54px;height:54px">
                <i class="bi {{ $portalConfig['icon'] }} fs-4"></i>
            </span>
        </div>
        <h1 class="h4 fw-bold mb-1">{{ $portalConfig['title'] }}</h1>
        <p class="text-secondary small mb-0">{{ $portalConfig['subtitle'] }}</p>
    </div>

    <form method="POST" action="{{ url($portal.'/login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email Petugas</label>
            <div class="jhs-input-group @error('email') is-invalid @enderror">
                <span class="jhs-input-group-icon"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="form-control"
                       placeholder="{{ $portal }}@josjishospital.id" required autofocus autocomplete="username">
            </div>
            @error('email')
                <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="password" class="form-label mb-0">Kata Sandi</label>
                <a href="{{ route('password.request') }}" class="small text-decoration-none" tabindex="-1">Lupa sandi?</a>
            </div>
            <div class="jhs-input-group @error('password') is-invalid @enderror">
                <span class="jhs-input-group-icon"><i class="bi bi-lock"></i></span>
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
                <label class="form-check-label small text-secondary" for="remember">Ingat sesi saya</label>
            </div>
            <span class="badge bg-{{ $portalConfig['color'] }} bg-opacity-10 text-{{ $portalConfig['color'] }} rounded-pill">
                <i class="bi {{ $portalConfig['icon'] }} me-1"></i>{{ $portalConfig['role']->label() }}
            </span>
        </div>

        <button type="submit" class="btn btn-{{ $portalConfig['color'] }} w-100 py-2 fw-semibold shadow-sm">
            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk sebagai {{ $portalConfig['role']->label() }}
        </button>
    </form>

    @if ($demoAccount)
        <div class="jhs-demo-box jhs-demo-credentials mt-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-bold text-dark">
                    <i class="bi bi-person-badge text-{{ $portalConfig['color'] }} me-1"></i>Akun Demo {{ $portalConfig['role']->label() }}
                </span>
                <span class="badge bg-{{ $portalConfig['color'] }} bg-opacity-10 text-{{ $portalConfig['color'] }}" style="font-size: 0.68rem;">Klik untuk Isi Otomatis</span>
            </div>
            <button type="button" class="jhs-demo-btn js-fill-demo w-100 justify-content-center"
                    data-email="{{ $demoAccount['email'] }}" data-password="{{ $demoAccount['password'] }}">
                <i class="bi bi-magic text-{{ $portalConfig['color'] }}"></i>
                <span>Isi {{ $demoAccount['email'] }}</span>
            </button>
        </div>
    @endif

    <div class="text-center mt-3 pt-3 border-top">
        <p class="small text-secondary mb-2">Bukan petugas {{ $portalConfig['role']->label() }}?</p>
        <div class="d-flex flex-wrap justify-content-center gap-1">
            <a href="{{ route('login') }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-decoration-none">
                <i class="bi bi-person me-1"></i>Portal Pasien
            </a>
            @foreach (config('jhs.staff_portals') as $key => $item)
                @continue($key === $portal)
                <a href="{{ route($key.'.login') }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-decoration-none">
                    <i class="bi {{ $item['icon'] }} me-1"></i>{{ $item['role']->shortLabel() }}
                </a>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleIcon.classList.toggle('bi-eye', !isPassword);
            toggleIcon.classList.toggle('bi-eye-slash', isPassword);
        });
    }

    document.querySelectorAll('.js-fill-demo').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('email').value = button.dataset.email;
            document.getElementById('password').value = button.dataset.password;
            button.classList.add('active');
        });
    });
</script>
@endpush
