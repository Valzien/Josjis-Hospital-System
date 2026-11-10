@extends('layouts.app')

@section('title', 'Buat Tagihan')

@section('content')
    <x-page-header title="Buat Tagihan" description="Rincian biaya kunjungan {{ $queue->queue_number }}." icon="bi-receipt">
        <x-slot:actions>
            <a href="{{ route('cashier.invoices.pending') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </x-slot:actions>
    </x-page-header>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Tagihan gagal dibuat:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($preview['record'] === null)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Kunjungan ini belum memiliki rekam medis sehingga belum dapat ditagih.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Pasien & Kunjungan" icon="bi-person-vcard">
                <div class="row g-3 small">
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Pasien</div>
                        <div class="fw-semibold">{{ $queue->patient?->name ?? '-' }}</div>
                        <div class="fs-7 text-muted-2">{{ $queue->patient?->medical_record_number ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Dokter</div>
                        <div class="fw-semibold">{{ $queue->doctor?->name ?? '-' }}</div>
                        <div class="fs-7 text-muted-2">{{ $queue->doctor?->specialization ?? '-' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">Tanggal Kunjungan</div>
                        <div class="fw-semibold">{{ $queue->queue_date?->translatedFormat('l, d F Y') }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted-2 fs-7">No. Antrean</div>
                        <div class="fw-semibold">{{ $queue->queue_number }}</div>
                    </div>
                </div>
            </x-card>

            <x-card title="Rincian Biaya" icon="bi-list-check" class="mt-3">
                <div class="jhs-table-wrap">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Komponen</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga Satuan</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($preview['items'] as $item)
                                <tr>
                                    <td>
                                        <div class="small fw-semibold">{{ $item['description'] }}</div>
                                        <span class="jhs-badge {{ $item['type'] === 'consultation' ? 'jhs-badge-primary' : 'jhs-badge-info' }} mt-1">
                                            <i class="bi {{ $item['type'] === 'consultation' ? 'bi-stethoscope' : 'bi-capsule' }}"></i>
                                            {{ $item['type'] === 'consultation' ? 'Konsultasi' : 'Obat' }}
                                        </span>
                                    </td>
                                    <td class="text-center small">{{ $item['quantity'] }}</td>
                                    <td class="text-end small text-nowrap">Rp {{ number_format((float) $item['unit_price'], 0, ',', '.') }}</td>
                                    <td class="text-end small fw-semibold text-nowrap">Rp {{ number_format((float) $item['subtotal'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <x-empty-state icon="bi-receipt" title="Belum ada rincian biaya"
                                                      text="Rekam medis kunjungan ini belum memiliki komponen biaya." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Ringkasan Tagihan" icon="bi-calculator">
                <form method="POST" action="{{ route('cashier.invoices.store') }}" id="invoiceForm">
                    @csrf
                    <input type="hidden" name="queue_id" value="{{ $queue->id }}">

                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted-2">Subtotal</span>
                        <span class="fw-semibold" id="summarySubtotal">Rp {{ number_format((float) $preview['subtotal'], 0, ',', '.') }}</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="discount">Potongan (Rp)</label>
                        <input type="number" name="discount" id="discount" min="0" step="1"
                               max="{{ (int) $preview['subtotal'] }}"
                               value="{{ old('discount', 0) }}"
                               class="form-control @error('discount') is-invalid @enderror">
                        <div class="form-text">Kosongkan atau isi 0 bila tanpa potongan.</div>
                        @error('discount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex justify-content-between border-top pt-2 mb-3">
                        <span class="fw-semibold">Total Tagihan</span>
                        <span class="fw-bold text-primary" id="summaryTotal">Rp {{ number_format((float) $preview['subtotal'], 0, ',', '.') }}</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="notes">Catatan</label>
                        <textarea name="notes" id="notes" rows="2" maxlength="500"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="Opsional">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100"
                            @disabled($preview['record'] === null || $preview['items'] === [])>
                        <i class="bi bi-receipt me-1"></i>Buat Tagihan
                    </button>
                    <p class="fs-7 text-muted-2 mb-0 mt-2">
                        <i class="bi bi-info-circle me-1"></i>Biaya konsultasi mengikuti pengaturan sistem
                        (Rp {{ number_format($consultationFee, 0, ',', '.') }}).
                    </p>
                </form>
            </x-card>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const subtotal = {{ (float) $preview['subtotal'] }};
            const discount = document.getElementById('discount');
            const totalEl = document.getElementById('summaryTotal');
            const formatter = new Intl.NumberFormat('id-ID');

            function formatRupiah(value) {
                return 'Rp ' + formatter.format(Math.max(0, value));
            }

            function refresh() {
                const value = Math.min(Math.max(parseFloat(discount.value || '0') || 0, 0), subtotal);
                if (totalEl) totalEl.textContent = formatRupiah(subtotal - value);
            }

            if (discount) {
                discount.addEventListener('input', refresh);
                refresh();
            }
        })();
    </script>
@endpush
