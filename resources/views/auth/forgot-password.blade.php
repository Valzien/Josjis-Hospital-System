@extends('layouts.guest')

@section('title', 'Lupa Kata Sandi')

@section('content')
    <div class="text-center mb-4">
        <div class="d-inline-flex p-2 rounded-4 bg-primary bg-opacity-10 mb-3 shadow-xs">
            <img src="{{ asset('images/logo-icon.png') }}" alt="{{ config('jhs.name') }}" height="56" class="d-inline-block">
        </div>
        <h1 class="h4 fw-bold mb-1">Lupa Kata Sandi</h1>
        <p class="text-secondary small mb-0">Masukkan email terdaftar, kami akan mengirim tautan pengaturan ulang.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror" required autofocus>
            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-send me-2"></i>Kirim Tautan Reset
        </button>
    </form>

    <div class="text-center mt-4">
        <a href="{{ route('login') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Kembali ke halaman masuk</a>
    </div>
@endsection