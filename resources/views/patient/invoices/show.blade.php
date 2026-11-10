@extends('layouts.app')

@section('title', 'Detail Tagihan '.$invoice->code)

@section('content')
    <x-page-header :title="'Tagihan '.$invoice->code"
                   :description="$invoice->created_at?->translatedFormat('l, d F Y H:i')"
                   icon="bi-receipt">
        <x-slot:actions>
            <a href="{{ route('patient.invoices.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($invoice->isPaid())
        <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill"></i>
            <div>Tagihan ini sudah <strong>lunas</strong>.</div>
        </div>
    @elseif ($invoice->status === \App\Enums\InvoiceStatus::Cancelled)
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle-fill"></i>
            <div>Tagihan ini telah <strong>dibatalkan</strong>.</div>
        </div>
    @else
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>Tagihan belum lunas. Silakan lakukan pembayaran di meja kasir rumah sakit.</div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Rincian Biaya" icon="bi-list-check">
                <div class="jhs-table-wrap">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Komponen</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="small fw-semibold">{{ $item->description }}</td>
                                    <td class="text-center small">{{ $item->quantity }}</td>
                                    <td class="text-end small text-nowrap">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                                    <td class="text-end small fw-semibold text-nowrap">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end small text-muted-2">Subtotal</td>
                                <td class="text-end small text-nowrap">Rp {{ number_format((float) $invoice->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @if ((float) $invoice->discount > 0)
                                <tr>
                                    <td colspan="3" class="text-end small text-muted-2">Potongan</td>
                                    <td class="text-end small text-nowrap text-danger">- Rp {{ number_format((float) $invoice->discount, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="3" class="text-end fw-semibold">Total</td>
                                <td class="text-end fw-bold text-nowrap">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-card>

            <x-card title="Riwayat Pembayaran" icon="bi-cash-stack" class="mt-3">
                <div class="jhs-table-wrap">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Metode</th>
                                <th>Waktu</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoice->payments as $payment)
                                <tr>
                                    <td class="small fw-semibold text-nowrap">{{ $payment->code }}</td>
                                    <td class="small">{{ $payment->methodLabel() }}</td>
                                    <td class="small text-nowrap text-muted-2">{{ $payment->paid_at?->translatedFormat('d M Y H:i') }}</td>
                                    <td class="text-end small fw-semibold text-nowrap text-success">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <x-empty-state icon="bi-cash-stack" title="Belum ada pembayaran"
                                                      text="Belum ada pembayaran yang tercatat untuk tagihan ini." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Ringkasan" icon="bi-calculator">
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted-2">Status</span>
                    <x-status-badge :status="$invoice->status" />
                </div>
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted-2">Total Tagihan</span>
                    <span class="fw-semibold">Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted-2">Sudah Dibayar</span>
                    <span class="fw-semibold text-success">Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex justify-content-between border-top pt-2 mb-2">
                    <span class="fw-semibold">Sisa Tagihan</span>
                    <span class="fw-bold {{ $invoice->remainingAmount() > 0 ? 'text-danger' : 'text-success' }}">
                        Rp {{ number_format($invoice->remainingAmount(), 0, ',', '.') }}
                    </span>
                </div>
                <div class="progress mb-1" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar"
                         style="width: {{ $invoice->progressPercent() }}%"
                         aria-valuenow="{{ $invoice->progressPercent() }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="fs-7 text-muted-2 text-end">{{ $invoice->progressPercent() }}% terbayar</div>
            </x-card>

            <x-card title="Informasi Kunjungan" icon="bi-info-circle" class="mt-3">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted-2 fw-normal">Dokter</dt>
                    <dd class="col-7 mb-2">{{ $invoice->queue?->doctor?->name ?? '-' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">No. Antrean</dt>
                    <dd class="col-7 mb-2">{{ $invoice->queue?->queue_number ?? '-' }}</dd>
                    <dt class="col-5 text-muted-2 fw-normal">Tanggal</dt>
                    <dd class="col-7 mb-0">{{ $invoice->created_at?->translatedFormat('d F Y') }}</dd>
                </dl>
            </x-card>
        </div>
    </div>
@endsection
