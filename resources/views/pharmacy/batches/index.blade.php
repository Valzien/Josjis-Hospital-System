@extends('layouts.app')

@section('title', 'Batch & Kedaluwarsa')

@section('content')
    <x-page-header title="Batch & Kedaluwarsa"
                   description="Pantau nomor batch obat, masa kedaluwarsa, dan musnahkan stok kedaluwarsa."
                   icon="bi-boxes">
        <x-slot:actions>
            <a href="{{ route('pharmacy.stock.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-box-seam me-1"></i>Manajemen Stok
            </a>
            <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-capsule me-1"></i>Data Obat
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Batch Aktif" :value="$stats['active']" icon="bi-boxes" color="primary" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Mendekati Expired" :value="$stats['expiring']" icon="bi-exclamation-triangle" color="warning"
                         :href="route('pharmacy.batches.index', ['status' => 'expiring_soon'])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Batch Kedaluwarsa" :value="$stats['expired']" icon="bi-x-octagon" color="danger"
                         :href="route('pharmacy.batches.index', ['status' => 'expired'])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Qty Kedaluwarsa" :value="$stats['expired_qty']" icon="bi-trash3" color="danger" />
        </div>
    </div>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small mb-1" for="q">Cari Batch</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nomor batch, nama, atau kode obat" data-search-submit>
            </div>
            <div class="col-lg-4">
                <label class="form-label small mb-1" for="status">Status Kedaluwarsa</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">Semua Batch</option>
                    <option value="safe" @selected(($filters['status'] ?? '') === 'safe')>Aman (&gt; 30 hari / tanpa tanggal)</option>
                    <option value="expiring_soon" @selected(($filters['status'] ?? '') === 'expiring_soon')>Mendekati (&le; 30 hari)</option>
                    <option value="expired" @selected(($filters['status'] ?? '') === 'expired')>Kedaluwarsa</option>
                </select>
            </div>
            <div class="col-lg-3 d-flex justify-content-end">
                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
            </div>
        </form>
    </x-card>

    <x-card title="Daftar Batch" subtitle="Urutan berdasarkan tanggal kedaluwarsa terdekat (FEFO)" icon="bi-boxes">
        <div class="table-responsive">
            <table class="table table-hover align-middle jhs-table-compact">
                <thead>
                    <tr>
                        <th>Nomor Batch</th>
                        <th>Obat</th>
                        <th class="text-center">Stok Batch</th>
                        <th>Kedaluwarsa</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        <tr>
                            <td class="small fw-semibold">{{ $batch->batch_number }}</td>
                            <td>
                                <div class="small fw-semibold">{{ $batch->medicine?->name ?? '-' }}</div>
                                <div class="fs-7 text-muted-2">{{ $batch->medicine?->medicine_code }}</div>
                            </td>
                            <td class="small text-center">{{ $batch->quantity }} {{ $batch->medicine?->unit }}</td>
                            <td class="small">{{ $batch->expiryLabel() }}</td>
                            <td>
                                <x-badge :text="$batch->statusLabel()" :color="$batch->statusColor()"
                                         :icon="$batch->isExpired() ? 'bi-x-circle-fill' : ($batch->isExpiringSoon() ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill')" />
                            </td>
                            <td class="text-end">
                                @if ($batch->isExpired())
                                    <form method="POST" action="{{ route('pharmacy.batches.dispose', $batch) }}"
                                          onsubmit="return confirm('Musnahkan batch {{ $batch->batch_number }} ({{ $batch->quantity }} {{ $batch->medicine?->unit }})?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash3 me-1"></i>Musnahkan
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('pharmacy.medicines.show', $batch->medicine_id) }}"
                                       class="btn btn-light border btn-sm">
                                        <i class="bi bi-eye me-1"></i>Detail
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"><x-empty-state icon="bi-boxes" title="Belum ada batch" /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($batches->hasPages())
            <div class="mt-3">{{ $batches->links('pagination::bootstrap-5') }}</div>
        @endif
    </x-card>
@endsection
