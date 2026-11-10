@extends('layouts.app')

@section('title', 'Dashboard Kasir')

@section('content')
    <x-page-header title="Dashboard Kasir" description="Ringkasan penerimaan dan tagihan hari ini." icon="bi-cash-coin">
        <x-slot:actions>
            <a href="{{ route('cashier.invoices.pending') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-clipboard2-check me-1"></i>Kunjungan Siap Ditagih
            </a>
            <a href="{{ route('cashier.invoices.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-receipt me-1"></i>Semua Tagihan
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Penerimaan Hari Ini"
                         :value="'Rp '.number_format($summary['revenue'], 0, ',', '.')"
                         icon="bi-cash-stack" color="success" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Transaksi Hari Ini" :value="number_format($summary['payments'], 0, ',', '.')"
                         icon="bi-receipt-cutoff" color="primary" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Tagihan Belum Lunas" :value="number_format($summary['unpaid'], 0, ',', '.')"
                         icon="bi-hourglass-split" color="warning"
                         :href="route('cashier.invoices.index', ['status' => 'UNPAID'])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Kunjungan Siap Ditagih" :value="number_format($summary['billable'], 0, ',', '.')"
                         icon="bi-clipboard2-check" color="info"
                         :href="route('cashier.invoices.pending')" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <x-card title="Tagihan Belum Lunas" icon="bi-hourglass-split"
                    subtitle="Tagihan terbuka yang menunggu pembayaran">
                <x-slot:actions>
                    <a href="{{ route('cashier.invoices.index', ['status' => 'UNPAID']) }}" class="btn btn-sm btn-light border">Lihat Semua</a>
                </x-slot:actions>

                <x-data-table :rows="$unpaidInvoices" :columns="[
                    ['label' => 'Kode', 'key' => 'code'],
                    ['label' => 'Pasien', 'key' => 'patient'],
                    ['label' => 'Total', 'key' => 'total', 'class' => 'text-end'],
                    ['label' => 'Status', 'key' => 'status'],
                ]" empty-title="Tidak ada tagihan terbuka" empty-icon="bi-check2-circle">
                    @forelse ($unpaidInvoices as $invoice)
                        <tr onclick="window.location.href='{{ route('cashier.invoices.show', $invoice) }}'" style="cursor:pointer">
                            <td class="small fw-semibold text-nowrap">{{ $invoice->code }}</td>
                            <td>
                                <div class="small fw-semibold text-truncate">{{ $invoice->patient?->name ?? '-' }}</div>
                                <div class="fs-7 text-muted-2">{{ $invoice->patient?->medical_record_number ?? '-' }}</div>
                            </td>
                            <td class="text-end small text-nowrap">
                                <div class="fw-semibold">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</div>
                                <div class="fs-7 text-danger">Sisa Rp {{ number_format($invoice->remainingAmount(), 0, ',', '.') }}</div>
                            </td>
                            <td><x-status-badge :status="$invoice->status" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"><x-empty-state icon="bi-check2-circle" title="Tidak ada tagihan terbuka" /></td>
                        </tr>
                    @endforelse
                </x-data-table>
            </x-card>
        </div>

        <div class="col-xl-5">
            <x-card title="Pembayaran Terakhir" icon="bi-cash-stack" subtitle="Transaksi terbaru yang dicatat kasir">
                <x-slot:actions>
                    <a href="{{ route('cashier.payments.index') }}" class="btn btn-sm btn-light border">Riwayat</a>
                </x-slot:actions>

                @forelse ($recentPayments as $payment)
                    <div class="d-flex align-items-center justify-content-between gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                        <div class="min-w-0">
                            <div class="small fw-semibold text-truncate">{{ $payment->invoice?->patient?->name ?? '-' }}</div>
                            <div class="fs-7 text-muted-2">
                                {{ $payment->code }} &middot; {{ $payment->methodLabel() }} &middot;
                                {{ $payment->paid_at?->translatedFormat('d M Y H:i') }}
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="small fw-bold text-success">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</div>
                            <a href="{{ route('cashier.invoices.show', $payment->invoice_id) }}" class="fs-7 text-decoration-none">Lihat tagihan</a>
                        </div>
                    </div>
                @empty
                    <x-empty-state icon="bi-cash-stack" title="Belum ada pembayaran"
                                  text="Pembayaran yang Anda catat akan muncul di sini." />
                @endforelse
            </x-card>
        </div>
    </div>
@endsection
