@extends('layouts.guest')

@section('title', 'Daftar Pasien')

@section('content')
    <div class="text-center mb-4">
        <span class="jhs-logo-mark jhs-logo-mark-lg mb-3 d-inline-flex">+</span>
        <h1 class="h4 fw-bold mb-1">Daftar Akun Pasien</h1>
        <p class="text-secondary mb-0">Lengkapi data dasar, lalu lengkapi profil saat pertama masuk.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div class="row g-3">
            <div class="col-12">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       class="form-control @error('name') is-invalid @enderror" required autofocus>
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror" required autocomplete="email">
                @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-6">
                <label for="phone" class="form-label">Nomor Telepon <span class="text-secondary">(opsional)</span></label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                       class="form-control @error('phone') is-invalid @enderror" placeholder="08xxxxxxxxxx">
                @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-6">
                <label for="gender" class="form-label">Jenis Kelamin</label>
                <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror">
                    <option value="">Pilih...</option>
                    @foreach (\App\Enums\Gender::options() as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('gender')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-6">
                <label for="birth_date" class="form-label">Tanggal Lahir</label>
                <input type="date" name="birth_date" id="birth_date" value="{{ old('birth_date') }}"
                       class="form-control @error('birth_date') is-invalid @enderror" max="{{ date('Y-m-d') }}">
                @error('birth_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-6">
                <label for="password" class="form-label">Kata Sandi</label>
                <input type="password" name="password" id="password"
                       class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                       class="form-control" required autocomplete="new-password">
            </div>

            <div class="col-12">
                <div class="form-check small text-secondary">
                    <input class="form-check-input" type="checkbox" id="agree" required>
                    <label class="form-check-label" for="agree">
                        Saya menyetujui pemrosesan data pribadi sesuai kebijakan rumah sakit.
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 mt-4">
            <i class="bi bi-person-plus me-2"></i>Buat Akun
        </button>
    </form>

    <div class="text-center mt-4">
        <p class="small text-secondary mb-0">Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a></p>
    </div>
@endsection