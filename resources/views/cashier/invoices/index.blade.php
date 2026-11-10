@extends('layouts.app')

@section('title', 'Tagihan')

@section('content')
    <x-page-header title="Tagihan Kunjungan" description="Seluruh invoice pasien beserta status pembayarannya." icon="bi-receipt">
        <x-slot:actions>
            <a href="{{ route('cashier.invoices.pending') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Buat Tagihan
            </a>
            <a href="{{ route('cashier.payments.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-cash-stack me-1"></i>Pembayaran
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Penerimaan Hari Ini" :value="'Rp '.number_format($summary['revenue'], 0, ',', '.')"
                         icon="bi-cash-stack" color="success" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Tagihan Dibuat Hari Ini" :value="number_format($summary['invoices_today'], 0, ',', '.')"
                         icon="bi-receipt" color="primary" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Tagihan Terbuka" :value="number_format($summary['unpaid'], 0, ',', '.')"
                         icon="bi-hourglass-split" color="warning" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Siap Ditagih" :value="number_format($summary['billable'], 0, ',', '.')"
                         icon="bi-clipboard2-check" color="info" />
        </div>
    </div>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small mb-1" for="q">Cari Tagihan</label>
                <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                       placeholder="Kode invoice, nama pasien, No. RM" aria-label="Cari" data-search-submit>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm" aria-label="Status">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-lg-2">
                <label class="form-label small mb-1" for="date">Tanggal</label>
                <input type="date" name="date" id="date" value="{{ $filters['date'] ?? '' }}" class="form-control form-control-sm" aria-label="Tanggal">
            </div>
            <div class="col-md-3 col-lg-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('cashier.invoices.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$invoices" :columns="[
        ['label' => 'Invoice', 'key' => 'code'],
        ['label' => 'Pasien', 'key' => 'patient'],
        ['label' => 'Dokter', 'key' => 'doctor'],
        ['label' => 'Total', 'key' => 'total', 'class' => 'text-end'],
        ['label' => 'Dibayar', 'key' => 'paid', 'class' => 'text-end'],
        ['label' => 'Status', 'key' => 'status'],
        ['label' => 'Aksi', 'class' => 'text-end'],
    ]" empty-title="Belum ada tagihan" empty-icon="bi-receipt"
       empty-text="Tagihan dibuat dari kunjungan yang sudah memiliki rekam medis.">
        @forelse ($invoices as $invoice)
            <tr onclick="window.location.href='{{ route('cashier.invoices.show', $invoice) }}'" style="cursor:pointer">
                <td>
                    <div class="small fw-semibold text-nowrap">{{ $invoice->code }}</div>
                    <div class="fs-7 text-muted-2 text-nowrap">{{ $invoice->created_at?->translatedFormat('d M Y H:i') }}</div>
                </td>
                <td>
                    <div class="small fw-semibold text-truncate">{{ $invoice->patient?->name ?? '-' }}</div>
                    <div class="fs-7 text-muted-2">{{ $invoice->patient?->medical_record_number ?? '-' }}</div>
                </td>
                <td class="small text-truncate">{{ $invoice->queue?->doctor?->name ?? '-' }}</td>
                <td class="text-end small text-nowrap fw-semibold">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</td>
                <td class="text-end small text-nowrap">
                    <span class="text-success">Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</span>
                    @if ($invoice->isOpen() && $invoice->remainingAmount() > 0)
                        <div class="fs-7 text-danger">Sisa Rp {{ number_format($invoice->remainingAmount(), 0, ',', '.') }}</div>
                    @endif
                </td>
                <td><x-status-badge :status="$invoice->status" /></td>
                <td class="text-end">
                    <a href="{{ route('cashier.invoices.show', $invoice) }}" class="btn btn-sm btn-light border" title="Lihat detail"
                       onclick="event.stopPropagation()">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">
                    <x-empty-state icon="bi-receipt" title="Belum ada tagihan"
                                  text="Tagihan dibuat dari kunjungan yang sudah memiliki rekam medis." />
                </td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
