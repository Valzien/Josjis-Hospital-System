@extends('layouts.app')

@section('title', 'Obat Saya')

@section('content')
    <x-page-header title="Obat Saya"
                   description="Daftar obat yang pernah diresepkan untuk Anda dan katalog ketersediaan obat rumah sakit."
                   icon="bi-capsule">
        <x-slot:actions>
            <a href="{{ route('patient.prescriptions.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-file-earmark-medical me-1"></i>Resep Saya
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Navigasi Tab --}}
    <ul class="nav nav-pills mb-3 gap-2">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'my_medicines' ? 'active' : 'bg-white border' }}"
               href="{{ route('patient.medicines.index', ['tab' => 'my_medicines']) }}">
                <i class="bi bi-person-check me-1"></i>Obat Pernah Diterima
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'catalog' ? 'active' : 'bg-white border' }}"
               href="{{ route('patient.medicines.index', ['tab' => 'catalog']) }}">
                <i class="bi bi-collection me-1"></i>Katalog Farmasi RS
            </a>
        </li>
    </ul>

    @if ($tab === 'my_medicines')
        <x-card class="mb-3">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="my_medicines">
                <div class="col-lg-8">
                    <label class="form-label small mb-1" for="q">Cari Obat Riwayat Anda</label>
                    <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                           class="form-control form-control-sm" placeholder="Ketik nama obat..." data-search-submit>
                </div>
                <div class="col-lg-4 d-flex gap-2">
                    <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Cari</button>
                    @if ($filters['q'] ?? null)
                        <a href="{{ route('patient.medicines.index', ['tab' => 'my_medicines']) }}" class="btn btn-light border btn-sm">Reset</a>
                    @endif
                </div>
            </form>
        </x-card>

        <x-card>
            <div class="table-responsive">
                <table class="table table-hover align-middle jhs-table-compact mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Obat</th>
                            <th>No. Resep</th>
                            <th>Dokter</th>
                            <th>Jumlah</th>
                            <th>Dosis</th>
                            <th>Aturan Pakai</th>
                            <th>Status Resep</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($myMedicines as $detail)
                            <tr>
                                <td class="fw-semibold small">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-capsule text-primary"></i>
                                        <span>{{ $detail->medicine?->name ?? '-' }}</span>
                                    </div>
                                    <div class="fs-8 text-muted-2">{{ $detail->medicine?->category ?? '-' }}</div>
                                </td>
                                <td>
                                    @if ($detail->prescription)
                                        <a href="{{ route('patient.prescriptions.show', $detail->prescription) }}" class="small text-decoration-none fw-semibold">
                                            {{ $detail->prescription->code }}
                                        </a>
                                        <div class="fs-8 text-muted-2">{{ $detail->prescription->created_at?->translatedFormat('d M Y') }}</div>
                                    @else
                                        <span class="small text-muted">-</span>
                                    @endif
                                </td>
                                <td class="small">{{ $detail->prescription?->doctor?->name ?? '-' }}</td>
                                <td class="small text-nowrap">{{ $detail->quantity }} {{ $detail->medicine?->unit }}</td>
                                <td class="small">{{ $detail->dosage ?? '-' }}</td>
                                <td class="small">{{ $detail->instructions ?? '-' }}</td>
                                <td>
                                    @if ($detail->prescription)
                                        <x-status-badge :status="$detail->prescription->status" />
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center">
                                    <x-empty-state icon="bi-capsule" title="Belum ada riwayat obat"
                                                   text="Daftar obat yang diresepkan oleh dokter akan tercatat otomatis di sini setelah pemeriksaan." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        @if ($myMedicines->hasPages())
            <div class="mt-3">{{ $myMedicines->links('pagination::bootstrap-5') }}</div>
        @endif

    @else
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-1"></i>
            Halaman ini menampilkan katalog obat umum rumah sakit beserta ketersediaan stoknya di instalasi farmasi.
        </div>

        <x-card class="mb-3">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="catalog">
                <div class="col-lg-6">
                    <label class="form-label small mb-1" for="q">Cari Obat Katalog</label>
                    <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                           class="form-control form-control-sm" placeholder="Nama obat..." data-search-submit>
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
    @endif
@endsection
