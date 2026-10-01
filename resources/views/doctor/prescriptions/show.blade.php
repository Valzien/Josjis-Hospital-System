@extends('layouts.app')

@section('title', 'Resep '.$prescription->code)

@section('content')
    <x-page-header :title="'Resep '.$prescription->code"
                   :description="$prescription->patient->name.' &middot; '.$prescription->created_at?->translatedFormat('d F Y H:i')"
                   icon="bi-file-earmark-medical">
        <x-slot:actions>
            <a href="{{ route('doctor.prescriptions.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Resep Saya
            </a>
            <a href="{{ route('doctor.prescriptions.print', $prescription) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                <i class="bi bi-printer me-1"></i>Cetak Resep
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Informasi Resep" icon="bi-info-circle">
                <div class="mb-3"><x-status-badge :status="$prescription->status" /></div>

                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted-2 fw-normal">Pasien</dt>
                    <dd class="col-7">{{ $prescription->patient->name }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">No. RM</dt>
                    <dd class="col-7">{{ $prescription->patient->medical_record_number }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Dokter</dt>
                    <dd class="col-7">{{ $prescription->doctor?->name }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Dibuat</dt>
                    <dd class="col-7">{{ $prescription->created_at?->translatedFormat('d M Y H:i') }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Diproses</dt>
                    <dd class="col-7">{{ $prescription->processed_at?->translatedFormat('d M Y H:i') ?? 'Belum diproses' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Apoteker</dt>
                    <dd class="col-7">{{ $prescription->processedBy?->name ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Total</dt>
                    <dd class="col-7 fw-semibold">{{ \Illuminate\Support\Number::currency($prescription->total_price, 'IDR', 'id') }}</dd>
                </dl>
            </x-card>

            @if ($prescription->medicalRecord)
                <x-card title="Rekam Medis" icon="bi-clipboard2-pulse" class="mt-3">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted-2 fw-normal">No. Rekam</dt>
                        <dd class="col-7">{{ $prescription->medicalRecord->record_number }}</dd>

                        <dt class="col-5 text-muted-2 fw-normal">Diagnosis</dt>
                        <dd class="col-7">{{ $prescription->medicalRecord->diagnosis ?? '-' }}</dd>

                        <dt class="col-5 text-muted-2 fw-normal">Waktu</dt>
                        <dd class="col-7">{{ $prescription->medicalRecord->examined_at?->translatedFormat('d M Y H:i') }}</dd>
                    </dl>
                    <a href="{{ route('doctor.examinations.show', $prescription->medicalRecord) }}"
                       class="btn btn-sm btn-light border mt-2">Lihat Rekam Medis</a>
                </x-card>
            @endif
        </div>

        <div class="col-xl-8">
            <x-card title="Detail Obat" icon="bi-capsule">
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
                                    <td class="small fw-semibold">{{ $detail->medicine->name }}</td>
                                    <td class="small text-center">{{ $detail->quantity }} {{ $detail->medicine->unit }}</td>
                                    <td class="small">{{ $detail->dosage ?? '-' }}</td>
                                    <td class="small">{{ $detail->instructions ?? '-' }}</td>
                                    <td class="small text-end">{{ \Illuminate\Support\Number::currency($detail->subtotal, 'IDR', 'id') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="small text-muted-2">Resep belum memiliki detail obat.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-semibold">Total</td>
                                <td class="text-end fw-bold">{{ \Illuminate\Support\Number::currency($prescription->total_price, 'IDR', 'id') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if ($prescription->notes)
                    <div class="alert alert-light border small mt-3 mb-0">
                        <div class="fw-semibold mb-1">Catatan resep</div>
                        {{ $prescription->notes }}
                    </div>
                @endif
            </x-card>
        </div>
    </div>
@endsection