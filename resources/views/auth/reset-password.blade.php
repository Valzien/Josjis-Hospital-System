@extends('layouts.guest')

@section('title', 'Atur Kata Sandi Baru')

@section('content')
    <div class="text-center mb-4">
        <span class="jhs-logo-mark jhs-logo-mark-lg mb-3 d-inline-flex">+</span>
        <h1 class="h4 fw-bold mb-1">Kata Sandi Baru</h1>
        <p class="text-secondary mb-0">Pastikan kata sandi berbeda dari tiga kata sandi terakhir.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', $email) }}"
                   class="form-control @error('email') is-invalid @enderror" required>
            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Kata Sandi Baru</label>
            <input type="password" name="password" id="password"
                   class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
            @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   class="form-control" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-check2-circle me-2"></i>Simpan Kata Sandi
        </button>
    </form>
@endsection