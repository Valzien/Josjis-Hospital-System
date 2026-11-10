@extends('layouts.app')

@section('title', 'Transaksi Obat')

@section('content')
    <x-page-header title="Riwayat Transaksi" description="Seluruh pergerakan stok obat: barang masuk, keluar, dan penyesuaian." icon="bi-arrow-left-right" />

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label small mb-1" for="q">Cari Transaksi</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                       placeholder="Nama obat atau catatan" aria-label="Cari" data-search-submit>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="type">Tipe</label>
                <select name="type" id="type" class="form-select form-select-sm" aria-label="Tipe">
                    <option value="">Semua Tipe</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label small mb-1" for="medicine_id">Obat</label>
                <select name="medicine_id" id="medicine_id" class="form-select form-select-sm" aria-label="Obat">
                    <option value="">Semua Obat</option>
                    @foreach ($medicines as $medicine)
                        <option value="{{ $medicine->id }}" @selected((string) ($filters['medicine_id'] ?? '') === (string) $medicine->id)>{{ $medicine->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1">Periode</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control" title="Dari tanggal" aria-label="Dari">
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control" title="Sampai tanggal" aria-label="Sampai">
                </div>
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('pharmacy.transactions.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Total Transaksi" :value="$stats['total']" icon="bi-arrow-left-right" color="secondary" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Barang Masuk" :value="$stats['in']" icon="bi-box-arrow-in-down" color="success" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Barang Keluar" :value="$stats['out']" icon="bi-box-arrow-up" color="danger" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Penyesuaian" :value="$stats['adjustment']" icon="bi-sliders" color="info" />
        </div>
    </div>

    @if (! empty($dailyChart['labels']))
        <x-card title="Grafik Transaksi Harian" icon="bi-bar-chart-line" class="mb-3">
            <div style="min-height:280px">
                <div id="chartTransactions"></div>
            </div>
        </x-card>
    @endif

    <x-data-table :rows="$transactions" :columns="[
        ['label' => 'Waktu', 'key' => 'created_at'],
        ['label' => 'Obat', 'key' => 'medicine'],
        ['label' => 'Tipe', 'key' => 'type'],
        ['label' => 'Jumlah', 'key' => 'quantity'],
        ['label' => 'Stok', 'key' => 'stock'],
        ['label' => 'Petugas', 'key' => 'user'],
    ]" empty-title="Belum ada transaksi" empty-icon="bi-arrow-left-right">
        @forelse ($transactions as $transaction)
            <tr onclick="window.location.href='{{ route('pharmacy.medicines.show', $transaction->medicine) }}'">
                <td class="small text-nowrap text-muted-2">{{ $transaction->created_at?->translatedFormat('d M Y H:i') }}</td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $transaction->medicine->name }}</div>
                    <div class="fs-7 text-muted-2">{{ $transaction->medicine->medicine_code }}</div>
                </td>
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
                <td class="small text-center">{{ abs($transaction->quantity) }} {{ $transaction->medicine->unit }}</td>
                <td class="small text-center text-muted-2">{{ $transaction->stock_before }} &rarr; {{ $transaction->stock_after }}</td>
                <td class="small text-muted-2">{{ $transaction->user?->name ?? 'Sistem' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6"><x-empty-state icon="bi-arrow-left-right" title="Belum ada transaksi" /></td>
            </tr>
        @endforelse
    </x-data-table>
@endsection

@push('scripts')
    @if (! empty($dailyChart['labels']))
        <script src="{{ asset('vendor/apexcharts/js/apexcharts.min.js') }}"></script>
        <script>
            window.jhsChart('chartTransactions', {
                labels: @json($dailyChart['labels']),
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: @json($dailyChart['inbound']),
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22,163,74,.12)',
                        fill: true,
                        tension: .35,
                        borderWidth: 2,
                        pointRadius: 2,
                    },
                    {
                        label: 'Barang Keluar',
                        data: @json($dailyChart['outbound']),
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220,38,38,.10)',
                        fill: true,
                        tension: .35,
                        borderWidth: 2,
                        pointRadius: 2,
                    }
                ]
            });
        </script>
    @endif
@endpush