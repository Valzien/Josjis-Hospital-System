@extends('layouts.app')

@section('title', $record->record_number)

@section('content')
    <x-page-header :title="'Rekam Medis '.$record->record_number"
                   :description="$record->patient->name.' &middot; '.$record->doctor?->name"
                   icon="bi-journal-medical">
        <x-slot:actions>
            <a href="{{ route('doctor.medical-records.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Arsip Rekam Medis
            </a>
            <a href="{{ route('doctor.examinations.print', $record) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Identitas" icon="bi-person">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <x-avatar :name="$record->patient->name" />
                    <div class="min-w-0">
                        <div class="fw-bold text-truncate">{{ $record->patient->name }}</div>
                        <div class="fs-7 text-muted-2">{{ $record->patient->medical_record_number }}</div>
                    </div>
                </div>

                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted-2 fw-normal">NIK</dt>
                    <dd class="col-7">{{ $record->patient->nik ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Jenis Kelamin</dt>
                    <dd class="col-7">{{ $record->patient->genderLabel() }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Usia</dt>
                    <dd class="col-7">{{ $record->patient->age() ? $record->patient->age().' tahun' : '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Golongan Darah</dt>
                    <dd class="col-7">{{ $record->patient->blood_type?->label() ?? '-' }}</dd>

                    <dt class="col-5 text-muted-2 fw-normal">Antrean</dt>
                    <dd class="col-7">{{ $record->queue?->queue_number ?? '-' }}</dd>
                </dl>
            </x-card>

            <x-card title="Tanda Vital" icon="bi-heart-pulse" class="mt-3">
                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted-2 fw-normal">Suhu</dt>
                    <dd class="col-6">{{ $record->temperature ? $record->temperature.' &#176;C' : '-' }}</dd>

                    <dt class="col-6 text-muted-2 fw-normal">Tekanan Darah</dt>
                    <dd class="col-6">{{ $record->blood_pressure ? $record->blood_pressure.' mmHg' : '-' }}</dd>

                    <dt class="col-6 text-muted-2 fw-normal">Berat Badan</dt>
                    <dd class="col-6">{{ $record->weight ? $record->weight.' kg' : '-' }}</dd>

                    <dt class="col-6 text-muted-2 fw-normal">Tinggi Badan</dt>
                    <dd class="col-6">{{ $record->height ? $record->height.' cm' : '-' }}</dd>
                </dl>
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Hasil Pemeriksaan" icon="bi-file-earmark-medical">
                <dl class="row mb-0">
                    <dt class="col-sm-3 text-muted-2 fw-normal">Dokter</dt>
                    <dd class="col-sm-9">{{ $record->doctor?->name }}</dd>

                    <dt class="col-sm-3 text-muted-2 fw-normal">Waktu</dt>
                    <dd class="col-sm-9">{{ $record->examined_at?->translatedFormat('l, d F Y H:i') }}</dd>

                    <dt class="col-sm-3 text-muted-2 fw-normal">Keluhan</dt>
                    <dd class="col-sm-9">{{ $record->complaint ?? '-' }}</dd>

                    <dt class="col-sm-3 text-muted-2 fw-normal">Hasil</dt>
                    <dd class="col-sm-9" style="white-space:pre-line">{{ $record->examination_result ?? '-' }}</dd>

                    <dt class="col-sm-3 text-muted-2 fw-normal">Diagnosis</dt>
                    <dd class="col-sm-9">{{ $record->diagnosis ?? '-' }}</dd>

                    <dt class="col-sm-3 text-muted-2 fw-normal">Tindakan</dt>
                    <dd class="col-sm-9">{{ $record->treatment ?? '-' }}</dd>

                    <dt class="col-sm-3 text-muted-2 fw-normal">Catatan</dt>
                    <dd class="col-sm-9">{{ $record->notes ?? '-' }}</dd>
                </dl>
            </x-card>

            @if ($record->prescription)
                <x-card title="Resep" icon="bi-file-earmark-medical" class="mt-3">
                    <x-slot:actions>
                        <span class="badge bg-light border">{{ $record->prescription->code }}</span>
                    </x-slot:actions>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle jhs-table-compact">
                            <thead>
                                <tr>
                                    <th>Obat</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Dosis</th>
                                    <th>Aturan Pakai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($record->prescription->details as $detail)
                                    <tr>
                                        <td class="small">{{ $detail->medicine->name }}</td>
                                        <td class="small text-center">{{ $detail->quantity }} {{ $detail->medicine->unit }}</td>
                                        <td class="small">{{ $detail->dosage ?? '-' }}</td>
                                        <td class="small">{{ $detail->instructions ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="small text-muted-2">Resep belum memiliki detail obat.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif
        </div>
    </div>
@endsection