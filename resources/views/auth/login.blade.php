@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="text-center mb-4">
        <span class="jhs-logo-mark jhs-logo-mark-lg mb-3 d-inline-flex">+</span>
        <h1 class="h4 fw-bold mb-1">Masuk ke {{ config('jhs.name') }}</h1>
        <p class="text-secondary mb-0">Gunakan akun yang diberikan administrator rumah sakit.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="nama@josjishospital.id" required autofocus autocomplete="username">
                @error('email')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Kata Sandi</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required autocomplete="current-password">
                @error('password')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label small" for="remember">Ingat saya</label>
            </div>
            <a href="{{ route('password.request') }}" class="small text-decoration-none">Lupa kata sandi?</a>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
        </button>
    </form>

    <div class="text-center mt-4">
        <p class="small text-secondary mb-0">Belum punya akun pasien? <a href="{{ route('register') }}">Daftar di sini</a></p>
    </div>

    @if (config('jhs.demo_credentials_enabled'))
        <div class="jhs-demo-credentials mt-4">
            <p class="small fw-semibold mb-2"><i class="bi bi-info-circle me-1"></i>Akun demo</p>
            <div class="small text-secondary">
                @foreach (config('jhs.demo_accounts') as $role => $account)
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1 js-fill-demo"
                            data-email="{{ $account['email'] }}" data-password="{{ $account['password'] }}">
                        {{ $role }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.js-fill-demo').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('email').value = button.dataset.email;
            document.getElementById('password').value = button.dataset.password;
        });
    });
</script>
@endpush