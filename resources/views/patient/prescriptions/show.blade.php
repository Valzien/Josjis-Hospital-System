@extends('layouts.app')

@section('title', 'Resep '.$prescription->code)

@section('content')
    <x-page-header :title="'Resep '.$prescription->code"
                   :description="$prescription->doctor?->name.' &middot; '.$prescription->created_at?->translatedFormat('l, d F Y')"
                   icon="bi-file-earmark-medical">
        <x-slot:actions>
            <a href="{{ route('patient.prescriptions.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Resep Saya
            </a>
            <button type="button" class="btn btn-light border btn-sm" data-print>
                <i class="bi bi-printer me-1"></i>Cetak
            </button>
        </x-slot:actions>
    </x-page-header>

    @php $status = $prescription->status; @endphp

    @if ($status?->value === 'PENDING')
        <div class="alert alert-info">
            <i class="bi bi-hourglass-split me-1"></i>
            Resep Anda sedang menunggu diproses oleh apoteker.
        </div>
    @elseif ($status?->value === 'PROCESSING')
        <div class="alert alert-info">
            <i class="bi bi-capsule me-1"></i>
            Apoteker sedang menyiapkan obat Anda.
        </div>
    @elseif ($status?->value === 'READY')
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            Obat Anda sudah siap diambil di bagian farmasi.
        </div>
    @elseif ($status?->value === 'COMPLETED')
        <div class="alert alert-success">
            <i class="bi bi-bag-check me-1"></i>
            Resep ini sudah diserahkan. Terima kasih telah mengambil obat Anda.
        </div>
    @elseif ($status?->value === 'CANCELLED')
        <div class="alert alert-secondary">
            <i class="bi bi-x-circle me-1"></i>
            Resep ini telah dibatalkan. Silakan konsultasikan kembali kepada dokter.
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Informasi Resep" icon="bi-info-circle">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted-2 fw-normal">No. Resep</dt>
                    <dd class="col-7 fw-semibold">{{ $prescription->code }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Status</dt>
                    <dd class="col-7"><x-status-badge :status="$status" /></dd>

                    <dt class="col-5 text-muted-2 fw-normal">Dokter</dt>
                    <dd class="col-7">{{ $prescription->doctor?->name ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Tanggal</dt>
                    <dd class="col-7">{{ $prescription->created_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>

                    @if ($prescription->processed_at)
                        <dt class="col-5 text-muted-2 fw-normal">Diproses</dt>
                        <dd class="col-7">{{ $prescription->processed_at->translatedFormat('d M Y H:i') }}</dd>
                    @endif

                    @if ($prescription->processedBy)
                        <dt class="col-5 text-muted-2 fw-normal">Apoteker</dt>
                        <dd class="col-7">{{ $prescription->processedBy->name }}</dd>
                    @endif

                    @if ($prescription->medicalRecord)
                        <dt class="col-5 text-muted-2 fw-normal">Diagnosis</dt>
                        <dd class="col-7">{{ $prescription->medicalRecord->diagnosis ?? '-' }}</dd>
                    @endif

                    <dt class="col-5 text-muted-2 fw-normal">Total</dt>
                    <dd class="col-7 fw-semibold">{{ \Illuminate\Support\Number::currency($prescription->total_price, 'IDR', 'id') }}</dd>
                </dl>

                @if ($prescription->notes)
                    <div class="border-top mt-3 pt-3">
                        <div class="fs-7 text-muted-2 mb-1">Catatan Dokter</div>
                        <div class="small">{{ $prescription->notes }}</div>
                    </div>
                @endif
            </x-card>

            @if ($prescription->medicalRecord)
                <x-card title="Keterangan Klinis" icon="bi-clipboard2-pulse" class="mt-3">
                    <a href="{{ route('patient.history.show', $prescription->medicalRecord) }}"
                       class="btn btn-sm btn-light border">
                        <i class="bi bi-journal-text me-1"></i>Lihat Rekam Medis
                    </a>
                </x-card>
            @endif
        </div>

        <div class="col-xl-8">
            <x-card title="Rincian Obat" icon="bi-capsule" subtitle="{{ $prescription->details->count() }} jenis obat">
                <div class="table-responsive">
                    <table class="table table-hover align-middle jhs-table-compact">
                        <thead>
                            <tr>
                                <th>Obat</th>
                                <th class="text-center">Jumlah</th>
                                <th>Dosis</th>
                                <th>Aturan Pakai</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($prescription->details as $detail)
                                <tr>
                                    <td>
                                        <div class="small fw-semibold">{{ $detail->medicine?->name ?? '-' }}</div>
                                        <div class="fs-8 text-muted-2">{{ $detail->medicine?->medicine_code ?? '-' }}</div>
                                    </td>
                                    <td class="small text-center">{{ $detail->quantity }} {{ $detail->medicine?->unit }}</td>
                                    <td class="small">{{ $detail->dosage }}</td>
                                    <td class="small">{{ $detail->instructions }}</td>
                                    <td class="small text-end">
                                        {{ \Illuminate\Support\Number::currency($detail->subtotal, 'IDR', 'id') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"><x-empty-state icon="bi-capsule" title="Belum ada rincian obat" /></td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($prescription->details->isNotEmpty())
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Total</th>
                                    <th class="text-end">
                                        {{ \Illuminate\Support\Number::currency($prescription->total_price, 'IDR', 'id') }}
                                    </th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </x-card>

            <x-card title="Cara Penggunaan" icon="bi-info-circle" class="mt-3">
                <ul class="small mb-0 ps-3">
                    <li>Ikuti dosis dan aturan pakai yang tertulis pada setiap obat.</li>
                    <li>Minum obat secara rutin sesuai jadwal yang diberikan dokter.</li>
                    <li>Simpan obat di tempat sejuk dan kering, jauh dari jangkauan anak-anak.</li>
                    <li>Jika muncul alergi atau efek samping, segera hubungi fasilitas kesehatan.</li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection