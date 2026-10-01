@extends('layouts.app')

@section('title', 'Obat Diberikan')

@section('content')
    <x-page-header title="Obat Diberikan"
                   description="Daftar obat yang tersedia di instalasi farmasi. Resep yang sudah selesai dapat diambil di bagian farmasi."
                   icon="bi-capsule">
        <x-slot:actions>
            <a href="{{ route('patient.prescriptions.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-file-earmark-medical me-1"></i>Resep Saya
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        Halaman ini menampilkan katalog obat, bukan daftar obat yang Anda terima.
        Riwayat obat yang Anda terima dapat dilihat pada <a href="{{ route('patient.prescriptions.index') }}" class="alert-link">detail resep</a>.
    </div>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-6">
                <label class="form-label small mb-1" for="q">Cari Obat</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama obat" data-search-submit>
            </div>
            <div class="col-lg-4">
                <label class="form-label small mb-1" for="category">Kategori</label>
                <select name="category" id="category" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 d-grid">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
            </div>
        </form>
    </x-card>

    <div class="row g-3">
        @forelse ($medicines as $medicine)
            <div class="col-md-6 col-xl-4">
                <x-card class="h-100">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <span class="jhs-stat-icon jhs-icon-soft-primary"><i class="bi bi-capsule"></i></span>
                        @if ($medicine->isOutOfStock())
                            <x-badge text="Stok Habis" icon="bi-x-circle-fill" color="danger" />
                        @elseif ($medicine->isLowStock())
                            <x-badge text="Stok Terbatas" icon="bi-exclamation-triangle-fill" color="warning" />
                        @else
                            <x-badge text="Tersedia" icon="bi-check-circle-fill" color="success" />
                        @endif
                    </div>

                    <div class="fw-semibold mt-2">{{ $medicine->name }}</div>
                    <div class="fs-7 text-muted-2 mb-2">{{ $medicine->medicine_code }} &middot; {{ $medicine->category }}</div>

                    @if ($medicine->description)
                        <p class="small text-muted-2">{{ \Illuminate\Support\Str::limit($medicine->description, 90) }}</p>
                    @endif

                    <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top">
                        <span class="fs-7 text-muted-2">Satuan: {{ $medicine->unit }}</span>
                        <span class="fw-semibold">
                            {{ \Illuminate\Support\Number::currency($medicine->price, 'IDR', 'id') }}
                        </span>
                    </div>
                </x-card>
            </div>
        @empty
            <div class="col-12">
                <x-card>
                    <x-empty-state icon="bi-capsule" title="Obat tidak ditemukan"
                                  text="Coba ubah kata kunci atau kategori pencarian." />
                </x-card>
            </div>
        @endforelse
    </div>

    @if ($medicines->hasPages())
        <div class="mt-3">{{ $medicines->links('pagination::bootstrap-5') }}</div>
    @endif
@endsection