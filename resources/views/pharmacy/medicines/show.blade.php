@extends('layouts.app')

@section('title', $medicine->name)

@section('content')
    <x-page-header :title="$medicine->name"
                   :description="$medicine->medicine_code.' &middot; '.$medicine->category.' &middot; '.$medicine->unit"
                   icon="bi-capsule">
        <x-slot:actions>
            <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Data Obat
            </a>
            <a href="{{ route('pharmacy.medicines.edit', $medicine) }}" class="btn btn-light border btn-sm">
                <i class="bi bi-pencil me-1"></i>Ubah
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($medicine->isOutOfStock())
        <div class="alert alert-danger"><i class="bi bi-x-octagon me-1"></i>Stok obat ini habis.</div>
    @elseif ($medicine->isLowStock())
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Stok berada di bawah batas minimum ({{ $medicine->minimum_stock }} {{ $medicine->unit }}).
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Ringkasan" icon="bi-info-circle">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="fs-7 text-muted-2">Stok</div>
                        <div class="fs-5 fw-bold">{{ $medicine->stock }} {{ $medicine->unit }}</div>
                    </div>
                    <div class="col-6">
                        <div class="fs-7 text-muted-2">Stok Minimum</div>
                        <div class="fs-5 fw-bold">{{ $medicine->minimum_stock }}</div>
                    </div>
                    <div class="col-12">
                        <div class="fs-7 text-muted-2">Harga Satuan</div>
                        <div class="fs-5 fw-bold">{{ \Illuminate\Support\Number::currency($medicine->price, 'IDR', 'id') }}</div>
                    </div>
                    <div class="col-12">
                        <div class="fs-7 text-muted-2">Nilai Inventaris</div>
                        <div class="fw-semibold">{{ \Illuminate\Support\Number::currency($medicine->stock * $medicine->price, 'IDR', 'id') }}</div>
                    </div>
                </div>

                <x-progress :current="$medicine->stock - $medicine->minimum_stock > 0 ? $medicine->minimum_stock : $medicine->stock"
                            :total="max($medicine->minimum_stock * 2, $medicine->stock, 1)"
                            color="{{ $medicine->isLowStock() ? 'bg-danger' : 'bg-success' }}" />

                <dl class="row mb-0 small mt-3">
                    <dt class="col-5 text-muted-2 fw-normal">Kategori</dt>
                    <dd class="col-7">{{ $medicine->category }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Satuan</dt>
                    <dd class="col-7">{{ $medicine->unit }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Status</dt>
                    <dd class="col-7">
                        @if ($medicine->status?->value === 'active')
                            <x-badge text="Aktif" icon="bi-check-circle-fill" color="success" />
                        @else
                            <x-badge text="Nonaktif" icon="bi-pause-circle-fill" color="secondary" />
                        @endif
                    </dd>

                    <dt class="col-5 text-muted-2 fw-normal">Dipakai Pada Resep</dt>
                    <dd class="col-7">{{ $medicine->prescription_details_count }} kali</dd>
                </dl>

                @if ($medicine->description)
                    <div class="border-top mt-3 pt-3">
                        <div class="fs-7 text-muted-2 mb-1">Deskripsi</div>
                        <div class="small">{{ $medicine->description }}</div>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Riwayat Stok" subtitle="{{ $transactions->total() }} transaksi" icon="bi-arrow-left-right">
                <div class="table-responsive">
                    <table class="table table-hover align-middle jhs-table-compact">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Tipe</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-center">Sebelum &rarr; Sesudah</th>
                                <th>Referensi</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td class="small text-nowrap text-muted-2">{{ $transaction->created_at?->translatedFormat('d M Y H:i') }}</td>
                                    <td>
                                        <x-badge :text="$transaction->type?->label() ?? '-'"
                                                 :icon="$transaction->type?->icon() ?? 'bi-dot'"
                                                 :color="match ($transaction->type?->value) {
                                                     'IN' => 'success',
                                                     'OUT' => 'danger',
                                                     'ADJUSTMENT' => 'info',
                                                     default => 'secondary',
                                                 }" />
                                    </td>
                                    <td class="small text-center">{{ abs($transaction->quantity) }}</td>
                                    <td class="small text-center text-muted-2">{{ $transaction->stock_before }} &rarr; {{ $transaction->stock_after }}</td>
                                    <td class="small text-muted-2">{{ $transaction->notes ?? $transaction->reference_type ?? '-' }}</td>
                                    <td class="small text-muted-2">{{ $transaction->user?->name ?? 'Sistem' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"><x-empty-state icon="bi-arrow-left-right" title="Belum ada transaksi" /></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div class="mt-3">{{ $transactions->links('pagination::bootstrap-5') }}</div>
                @endif
            </x-card>
        </div>
    </div>
@endsection