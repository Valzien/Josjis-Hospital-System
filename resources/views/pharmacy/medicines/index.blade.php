@extends('layouts.app')

@section('title', 'Data Obat')

@section('content')
    <x-page-header title="Data Obat" description="Katalog obat, harga, stok minimum, dan masa kedaluwarsa." icon="bi-capsule">
        <x-slot:actions>
            <a href="{{ route('pharmacy.stock.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-box-seam me-1"></i>Manajemen Stok
            </a>
            <a href="{{ route('pharmacy.medicines.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Tambah Obat
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="q">Cari Obat</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Nama atau kode obat" data-search-submit>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="category">Kategori</label>
                <select name="category" id="category" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="expiry">Kedaluwarsa</label>
                <select name="expiry" id="expiry" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="expiring_soon" @selected(($filters['expiry'] ?? '') === 'expiring_soon')>Mendekati (&lt; 30 Hari)</option>
                    <option value="expired" @selected(($filters['expiry'] ?? '') === 'expired')>Sudah Kedaluwarsa</option>
                </select>
            </div>
            <div class="col-lg-2 d-flex gap-2 align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="low_stock" value="1" id="low_stock"
                           @checked($filters['low_stock'] ?? false)>
                    <label class="form-check-label small" for="low_stock">Stok rendah</label>
                </div>
                <button class="btn btn-primary btn-sm ms-auto" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
            </div>
        </form>
    </x-card>

    @if (($expiredCount ?? 0) > 0)
        <div class="alert alert-danger d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <div>
                <i class="bi bi-x-circle-fill me-1"></i>
                Peringatan: Terdapat <strong>{{ $expiredCount }}</strong> obat yang telah melewati tanggal kedaluwarsa.
            </div>
            <a href="{{ route('pharmacy.medicines.index', ['expiry' => 'expired']) }}" class="btn btn-sm btn-danger">Tinjau Obat Kedaluwarsa</a>
        </div>
    @endif

    @if (($expiringSoonCount ?? 0) > 0)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <div>
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Terdapat <strong>{{ $expiringSoonCount }}</strong> obat yang mendekati tanggal kedaluwarsa (&lt; 30 hari).
            </div>
            <a href="{{ route('pharmacy.medicines.index', ['expiry' => 'expiring_soon']) }}" class="btn btn-sm btn-warning">Tinjau Obat Mendekati Expired</a>
        </div>
    @endif

    @if ($lowStockCount > 0)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>{{ $lowStockCount }}</strong> obat berada di bawah stok minimum.
            </div>
            <a href="{{ route('pharmacy.stock.index', ['low_stock' => 1]) }}" class="btn btn-sm btn-warning">Tinjau Stok</a>
        </div>
    @endif

    <x-data-table :rows="$medicines" :columns="[
        ['label' => 'Kode', 'key' => 'medicine_code'],
        ['label' => 'Nama Obat', 'key' => 'name'],
        ['label' => 'Kategori', 'key' => 'category'],
        ['label' => 'Stok', 'key' => 'stock'],
        ['label' => 'Kedaluwarsa', 'key' => 'expiry_date'],
        ['label' => 'Harga', 'key' => 'price'],
        ['label' => 'Status', 'key' => 'status'],
    ]" empty-title="Belum ada obat" empty-icon="bi-capsule">
        @forelse ($medicines as $medicine)
            <tr onclick="window.location.href='{{ route('pharmacy.medicines.show', $medicine) }}'">
                <td class="small fw-semibold">{{ $medicine->medicine_code }}</td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $medicine->name }}</div>
                    <div class="fs-7 text-muted-2">Minimum {{ $medicine->minimum_stock }} {{ $medicine->unit }}</div>
                </td>
                <td class="small">{{ $medicine->category }}</td>
                <td>
                    <x-badge :text="$medicine->stock.' '.$medicine->unit"
                             icon="bi-box-seam"
                             :color="match (true) {
                                 $medicine->stock <= 0 => 'danger',
                                 $medicine->isLowStock() => 'warning',
                                 default => 'success',
                             }" />
                </td>
                <td>
                    @if ($medicine->isExpired())
                        <x-badge :text="$medicine->expiry_date?->translatedFormat('d M Y')" icon="bi-x-circle-fill" color="danger" />
                    @elseif ($medicine->isExpiringSoon(30))
                        <x-badge :text="$medicine->expiry_date?->translatedFormat('d M Y')" icon="bi-exclamation-triangle-fill" color="warning" />
                    @elseif ($medicine->expiry_date)
                        <span class="small text-muted-2">{{ $medicine->expiry_date->translatedFormat('d M Y') }}</span>
                    @else
                        <span class="small text-muted-2">&mdash;</span>
                    @endif
                </td>
                <td class="small">{{ \Illuminate\Support\Number::currency($medicine->price, 'IDR', 'id') }}</td>
                <td>
                    @if ($medicine->status?->value === 'active')
                        <x-badge text="Aktif" icon="bi-check-circle-fill" color="success" />
                    @else
                        <x-badge text="Nonaktif" icon="bi-pause-circle-fill" color="secondary" />
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7"><x-empty-state icon="bi-capsule" title="Belum ada obat" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
