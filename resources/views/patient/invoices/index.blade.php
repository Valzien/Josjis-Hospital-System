@extends('layouts.app')

@section('title', 'Tagihan Saya')

@section('content')
    <x-page-header title="Tagihan Saya" description="Rincian biaya kunjungan dan status pembayaran Anda." icon="bi-receipt">
        <x-slot:actions>
            <a href="{{ route('patient.history.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-journal-medical me-1"></i>Riwayat Pemeriksaan
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($outstanding > 0)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <i class="bi bi-exclamation-triangle me-1"></i>
                Anda memiliki total tagihan belum lunas sebesar
                <strong>Rp {{ number_format($outstanding, 0, ',', '.') }}</strong>.
            </div>
            <span class="small">Pembayaran dilakukan di meja kasir rumah sakit.</span>
        </div>
    @endif

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small mb-1" for="status">Status</label>
                <select name="status" id="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                @if ($filters['status'] ?? null)
                    <a href="{{ route('patient.invoices.index') }}" class="btn btn-light border btn-sm">Reset</a>
                @endif
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$invoices" :columns="[
        ['label' => 'Invoice', 'key' => 'code'],
        ['label' => 'Tanggal', 'key' => 'date'],
        ['label' => 'Dokter', 'key' => 'doctor'],
        ['label' => 'Total', 'key' => 'total', 'class' => 'text-end'],
        ['label' => 'Status', 'key' => 'status'],
    ]" empty-title="Belum ada tagihan" empty-icon="bi-receipt"
       empty-text="Tagihan muncul setelah Anda menyelesaikan pemeriksaan.">
        @forelse ($invoices as $invoice)
            <tr onclick="window.location.href='{{ route('patient.invoices.show', $invoice) }}'" style="cursor:pointer">
                <td class="small fw-semibold text-nowrap">{{ $invoice->code }}</td>
                <td class="small text-nowrap text-muted-2">{{ $invoice->created_at?->translatedFormat('d M Y') }}</td>
                <td>
                    <div class="small text-truncate">{{ $invoice->queue?->doctor?->name ?? '-' }}</div>
                    <div class="fs-7 text-muted-2">{{ $invoice->queue?->doctor?->specialization ?? '-' }}</div>
                </td>
                <td class="text-end small text-nowrap">
                    <div class="fw-semibold">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</div>
                    @if ($invoice->isOpen() && $invoice->remainingAmount() > 0)
                        <div class="fs-7 text-danger">Sisa Rp {{ number_format($invoice->remainingAmount(), 0, ',', '.') }}</div>
                    @endif
                </td>
                <td><x-status-badge :status="$invoice->status" /></td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    <x-empty-state icon="bi-receipt" title="Belum ada tagihan"
                                  text="Tagihan muncul setelah Anda menyelesaikan pemeriksaan." />
                </td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
