@extends('layouts.guest')

@section('title', 'Atur Kata Sandi Baru')

@section('content')
    <div class="text-center mb-4">
        <div class="d-inline-flex p-2 rounded-4 bg-primary bg-opacity-10 mb-3 shadow-xs">
            <img src="{{ asset('images/logo-icon.png') }}" alt="{{ config('jhs.name') }}" height="56" class="d-inline-block">
        </div>
        <h1 class="h4 fw-bold mb-1">Kata Sandi Baru</h1>
        <p class="text-secondary small mb-0">Pastikan kata sandi baru Anda kuat dan mudah diingat.</p>
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