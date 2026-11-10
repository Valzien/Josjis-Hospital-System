@extends('layouts.app')

@section('title', 'Riwayat Pembayaran')

@section('content')
    <x-page-header title="Riwayat Pembayaran" description="Seluruh transaksi pembayaran yang diterima kasir." icon="bi-cash-stack">
        <x-slot:actions>
            <a href="{{ route('cashier.invoices.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-receipt me-1"></i>Daftar Tagihan
            </a>
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-1" for="date">Tanggal</label>
                <input type="date" name="date" id="date" value="{{ $filters['date'] ?? '' }}" class="form-control form-control-sm" aria-label="Tanggal">
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small mb-1" for="method">Metode</label>
                <select name="method" id="method" class="form-select form-select-sm" aria-label="Metode">
                    <option value="">Semua Metode</option>
                    @foreach ($methods as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['method'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a href="{{ route('cashier.payments.index') }}" class="btn btn-light border btn-sm">Reset</a>
            </div>
            <div class="col-md-3 col-lg-3 ms-lg-auto text-lg-end">
                <div class="fs-7 text-muted-2">{{ ($filters['date'] ?? null) ? 'Total pada filter tanggal' : 'Total hari ini' }}</div>
                <div class="fw-bold text-success">Rp {{ number_format($total, 0, ',', '.') }}</div>
            </div>
        </form>
    </x-card>

    <x-data-table :rows="$payments" :columns="[
        ['label' => 'Kode', 'key' => 'code'],
        ['label' => 'Tagihan', 'key' => 'invoice'],
        ['label' => 'Pasien', 'key' => 'patient'],
        ['label' => 'Metode', 'key' => 'method'],
        ['label' => 'Waktu', 'key' => 'time'],
        ['label' => 'Nominal', 'key' => 'amount', 'class' => 'text-end'],
    ]" empty-title="Belum ada pembayaran" empty-icon="bi-cash-stack"
       empty-text="Pembayaran yang Anda catat akan muncul di sini.">
        @forelse ($payments as $payment)
            <tr onclick="window.location.href='{{ route('cashier.invoices.show', $payment->invoice_id) }}'" style="cursor:pointer">
                <td class="small fw-semibold text-nowrap">{{ $payment->code }}</td>
                <td class="small text-nowrap">{{ $payment->invoice?->code ?? '-' }}</td>
                <td class="small text-truncate">{{ $payment->invoice?->patient?->name ?? '-' }}</td>
                <td>
                    <span class="jhs-badge jhs-badge-secondary">
                        <i class="bi {{ $payment->method?->icon() ?? 'bi-cash' }}"></i>{{ $payment->methodLabel() }}
                    </span>
                </td>
                <td class="small text-nowrap text-muted-2">{{ $payment->paid_at?->translatedFormat('d M Y H:i') }}</td>
                <td class="text-end small fw-semibold text-nowrap text-success">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <x-empty-state icon="bi-cash-stack" title="Belum ada pembayaran"
                                  text="Pembayaran yang Anda catat akan muncul di sini." />
                </td>
            </tr>
        @endforelse
    </x-data-table>
@endsection
