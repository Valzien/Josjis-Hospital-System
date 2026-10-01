@extends('layouts.app')

@section('title', 'Rekam Medis '.$record->record_number)

@section('content')
    <x-page-header :title="'Rekam Medis '.$record->record_number"
                   :description="$record->patient?->name.' &middot; '.($record->examined_at?->translatedFormat('l, d F Y H:i') ?? '-')"
                   icon="bi-journal-medical">
        <x-slot:actions>
            <a href="{{ route('patient.history.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Riwayat Pemeriksaan
            </a>
            <button type="button" class="btn btn-light border btn-sm" data-print>
                <i class="bi bi-printer me-1"></i>Cetak
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Identitas Pasien" icon="bi-person-vcard">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted-2 fw-normal">Nama</dt>
                    <dd class="col-7">{{ $record->patient?->name ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">No. Rekam Medis</dt>
                    <dd class="col-7">{{ $record->patient?->medical_record_number ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">NIK</dt>
                    <dd class="col-7">{{ $record->patient?->nik ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Jenis Kelamin / Usia</dt>
                    <dd class="col-7">{{ $record->patient?->genderLabel() ?? '-' }} / {{ $record->patient?->age() ? $record->patient->age().' tahun' : '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Golongan Darah</dt>
                    <dd class="col-7">{{ $record->patient?->blood_type?->label() ?? '-' }}</dd>

                    @if ($record->patient?->phone)
                        <dt class="col-5 text-muted-2 fw-normal">Telepon</dt>
                        <dd class="col-7">{{ $record->patient->phone }}</dd>
                    @endif
                </dl>
            </x-card>

            <x-card title="Vital Sign" icon="bi-activity" class="mt-3">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="fs-7 text-muted-2">Tekanan Darah</div>
                        <div class="fw-semibold">{{ $record->blood_pressure ? $record->blood_pressure.' mmHg' : '-' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="fs-7 text-muted-2">Suhu</div>
                        <div class="fw-semibold">{{ $record->temperature ? $record->temperature.' &#176;C' : '-' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="fs-7 text-muted-2">Berat Badan</div>
                        <div class="fw-semibold">{{ $record->weight ? $record->weight.' kg' : '-' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="fs-7 text-muted-2">Tinggi Badan</div>
                        <div class="fw-semibold">{{ $record->height ? $record->height.' cm' : '-' }}</div>
                    </div>
                </div>
            </x-card>

            <x-card title="Pemeriksaan" icon="bi-clipboard2-pulse" class="mt-3">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted-2 fw-normal">Dokter</dt>
                    <dd class="col-7">{{ $record->doctor?->name ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Waktu</dt>
                    <dd class="col-7">{{ $record->examined_at?->translatedFormat('d F Y H:i') ?? '-' }}</dd>

                    @if ($record->queue)
                        <dt class="col-5 text-muted-2 fw-normal">No. Antrean</dt>
                        <dd class="col-7">{{ $record->queue->queue_number }}</dd>
                    @endif
                </dl>
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Keluhan" icon="bi-chat-left-text">
                <p class="mb-0">{{ $record->complaint ?? '-' }}</p>
            </x-card>

            <x-card title="Hasil Pemeriksaan" icon="bi-search" class="mt-3">
                <p class="mb-0" style="white-space:pre-line">{{ $record->examination_result ?? '-' }}</p>
            </x-card>

            <x-card title="Diagnosis" icon="bi-clipboard2-pulse" class="mt-3">
                <p class="mb-0 fw-semibold" style="white-space:pre-line">{{ $record->diagnosis ?? '-' }}</p>
            </x-card>

            <x-card title="Tindakan / Terapi" icon="bi-clipboard2-heart" class="mt-3">
                <p class="mb-0" style="white-space:pre-line">{{ $record->treatment ?? '-' }}</p>
            </x-card>

            @if ($record->notes)
                <x-card title="Catatan Dokter" icon="bi-sticky" class="mt-3">
                    <p class="mb-0 small" style="white-space:pre-line">{{ $record->notes }}</p>
                </x-card>
            @endif

            <x-card title="Resep" icon="bi-file-earmark-medical" class="mt-3">
                @if ($record->prescription)
                    @php $prescription = $record->prescription; @endphp

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <div class="small fw-semibold">{{ $prescription->code }}</div>
                            <div class="fs-7 text-muted-2">{{ $prescription->created_at?->translatedFormat('d F Y H:i') }}</div>
                        </div>
                        <x-status-badge :status="$prescription->status" />
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle jhs-table-compact">
                            <thead>
                                <tr>
                                    <th>Obat</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Dosis</th>
                                    <th>Aturan Pakai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($prescription->details as $detail)
                                    <tr>
                                        <td class="small fw-semibold">{{ $detail->medicine?->name ?? '-' }}</td>
                                        <td class="small text-center">{{ $detail->quantity }} {{ $detail->medicine?->unit }}</td>
                                        <td class="small">{{ $detail->dosage }}</td>
                                        <td class="small">{{ $detail->instructions }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <a href="{{ route('patient.prescriptions.show', $prescription) }}" class="btn btn-sm btn-primary mt-2">
                        <i class="bi bi-file-earmark-medical me-1"></i>Lihat Detail Resep
                    </a>
                @else
                    <x-empty-state icon="bi-file-earmark-x" title="Tidak ada resep"
                                  text="Dokter tidak memberikan resep pada pemeriksaan ini." />
                @endif
            </x-card>
        </div>
    </div>
@endsection