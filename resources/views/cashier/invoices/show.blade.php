@extends('layouts.app')

@section('title', 'Tagihan '.$invoice->code)

@section('content')
    <x-page-header :title="'Tagihan '.$invoice->code"
                   :description="'Pasien '.($invoice->patient?->name ?? '-').' · '.($invoice->created_at?->translatedFormat('l, d F Y H:i') ?? '-')"
                   icon="bi-receipt">
        <x-slot:actions>
            <a href="{{ route('cashier.invoices.receipt', $invoice) }}" target="_blank" class="btn btn-light border btn-sm">
                <i class="bi bi-printer me-1"></i>Cetak Kuitansi
            </a>
            <a href="{{ route('cashier.invoices.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($invoice->isPaid())
        <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill"></i>
            <div>Tagihan ini sudah <strong>lunas</strong> pada {{ $invoice->paid_at?->translatedFormat('d F Y H:i') }}.</div>
        </div>
    @elseif ($invoice->status === \App\Enums\InvoiceStatus::Cancelled)
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-x-circle-fill"></i>
            <div>Tagihan ini telah <strong>dibatalkan</strong>.</div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Informasi Tagihan" icon="bi-info-circle">
                <div class="row g-3 small">
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Pasien</div>
                        <div class="fw-semibold">{{ $invoice->patient?->name ?? '-' }}</div>
                        <div class="fs-7 text-muted-2">{{ $invoice->patient?->medical_record_number ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Status</div>
                        <div><x-status-badge :status="$invoice->status" /></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Dokter</div>
                        <div class="fw-semibold">{{ $invoice->queue?->doctor?->name ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">No. Antrean</div>
                        <div class="fw-semibold">{{ $invoice->queue?->queue_number ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Dibuat Oleh</div>
                        <div class="fw-semibold">{{ $invoice->issuedBy?->name ?? 'Sistem' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Tanggal Tagihan</div>
                        <div class="fw-semibold">{{ $invoice->created_at?->translatedFormat('d F Y H:i') }}</div>
                    </div>
                    @if ($invoice->notes)
                        <div class="col-12">
                            <div class="text-muted-2 fs-7">Catatan</div>
                            <div class="text-pre-line">{{ $invoice->notes }}</div>
                        </div>
                    @endif
                </div>
            </x-card>

            <x-card title="Rincian Biaya" icon="bi-list-check" class="mt-3">
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
                                    <td>
                                        <div class="small fw-semibold">{{ $item->description }}</div>
                                        <span class="jhs-badge {{ $item->type === 'consultation' ? 'jhs-badge-primary' : 'jhs-badge-info' }} mt-1">
                                            <i class="bi {{ $item->type === 'consultation' ? 'bi-stethoscope' : 'bi-capsule' }}"></i>
                                            {{ $item->type === 'consultation' ? 'Konsultasi' : 'Obat' }}
                                        </span>
                                    </td>
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
                                <th>Kasir</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoice->payments as $payment)
                                <tr>
                                    <td class="small fw-semibold text-nowrap">{{ $payment->code }}</td>
                                    <td>
                                        <span class="jhs-badge jhs-badge-secondary">
                                            <i class="bi {{ $payment->method?->icon() ?? 'bi-cash' }}"></i>{{ $payment->methodLabel() }}
                                        </span>
                                    </td>
                                    <td class="small text-nowrap text-muted-2">{{ $payment->paid_at?->translatedFormat('d M Y H:i') }}</td>
                                    <td class="small text-truncate">{{ $payment->receivedBy?->name ?? '-' }}</td>
                                    <td class="text-end small fw-semibold text-nowrap text-success">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-empty-state icon="bi-cash-stack" title="Belum ada pembayaran"
                                                      text="Pembayaran yang diterima akan tercatat di sini." />
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

            @if ($invoice->isOpen() && $invoice->remainingAmount() > 0)
                <x-card title="Terima Pembayaran" icon="bi-wallet2" class="mt-3">
                    <form method="POST" action="{{ route('cashier.invoices.payments.store', $invoice) }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="amount">Nominal (Rp)</label>
                            <input type="number" name="amount" id="amount" min="1" step="1"
                                   max="{{ (int) $invoice->remainingAmount() }}"
                                   value="{{ old('amount', (int) $invoice->remainingAmount()) }}"
                                   class="form-control @error('amount') is-invalid @enderror" required>
                            <div class="form-text">Boleh dibayar sebagian (DP). Sisa: Rp {{ number_format($invoice->remainingAmount(), 0, ',', '.') }}.</div>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="method">Metode Pembayaran</label>
                            <select name="method" id="method" class="form-select @error('method') is-invalid @enderror" required>
                                @foreach ($methods as $value => $label)
                                    <option value="{{ $value }}" @selected(old('method', 'CASH') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="notes">Catatan</label>
                            <input type="text" name="notes" id="notes" maxlength="200" value="{{ old('notes') }}"
                                   class="form-control @error('notes') is-invalid @enderror" placeholder="Opsional">
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-lg me-1"></i>Simpan Pembayaran
                        </button>
                    </form>
                </x-card>
            @endif

            @if ($invoice->isOpen() && (float) $invoice->paid_amount <= 0)
                <x-card title="Batalkan Tagihan" icon="bi-x-circle" class="mt-3">
                    <form method="POST" action="{{ route('cashier.invoices.cancel', $invoice) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="reason">Alasan Pembatalan</label>
                            <input type="text" name="reason" id="reason" maxlength="200" class="form-control"
                                   placeholder="Contoh: salah input kunjungan">
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100"
                                data-confirm-title="Batalkan Tagihan"
                                data-confirm-message="Tagihan {{ $invoice->code }} akan dibatalkan dan tidak dapat dibayar lagi. Lanjutkan?">
                            <i class="bi bi-x-circle me-1"></i>Batalkan Tagihan
                        </button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
@endsection
