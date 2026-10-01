@extends('layouts.app')

@section('title', 'Detail Pasien')

@section('content')
    <x-page-header :title="$patient->name"
                   :description="'No. Rekam Medis '.$patient->medical_record_number.($patient->age() ? ' &middot; '.$patient->age().' tahun' : '')"
                   icon="bi-person-vcard">
        <x-slot:actions>
            <a href="{{ route('admin.patients.edit', $patient) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil me-1"></i>Ubah Data
            </a>
            <a href="{{ route('reception.queues.create', ['patient_id' => $patient->id]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-ticket-perforated me-1"></i>Buat Antrean
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Total Antrean" :value="$stats['queues']" icon="bi-list-ol" color="info" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Antrean Selesai" :value="$stats['completed']" icon="bi-check-circle" color="success" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Rekam Medis" :value="$stats['records']" icon="bi-journal-medical" color="primary" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Resep" :value="$stats['prescriptions']" icon="bi-file-earmark-medical" color="warning" />
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <x-card title="Data Diri" icon="bi-person">
                <dl class="row small mb-0">
                    <dt class="col-4 text-muted-2 fw-normal">Jenis Kelamin</dt>
                    <dd class="col-8">{{ $patient->genderLabel() }}</dd>
                    <dt class="col-4 text-muted-2 fw-normal">Tanggal Lahir</dt>
                    <dd class="col-8">{{ $patient->birth_date?->translatedFormat('d F Y') ?? '-' }}</dd>
                    <dt class="col-4 text-muted-2 fw-normal">Golongan Darah</dt>
                    <dd class="col-8">{{ $patient->blood_type?->label() ?? '-' }}</dd>
                    <dt class="col-4 text-muted-2 fw-normal">NIK</dt>
                    <dd class="col-8">{{ $patient->nik ?? '-' }}</dd>
                    <dt class="col-4 text-muted-2 fw-normal">Telepon</dt>
                    <dd class="col-8">{{ $patient->phone ?? '-' }}</dd>
                    <dt class="col-4 text-muted-2 fw-normal">Kontak Darurat</dt>
                    <dd class="col-8">{{ $patient->emergency_contact_name ?? '-' }}</dd>
                    <dt class="col-4 text-muted-2 fw-normal">Telepon Darurat</dt>
                    <dd class="col-8">{{ $patient->emergency_contact_phone ?? '-' }}</dd>
                    <dt class="col-4 text-muted-2 fw-normal">Akun</dt>
                    <dd class="col-8">{{ $patient->user?->email ?? 'Belum memiliki akun' }}</dd>
                </dl>

                <hr>

                <div class="small">
                    <div class="text-muted-2 mb-1">Alamat</div>
                    <div class="mb-3">{{ $patient->address ?? '-' }}</div>

                    <div class="text-muted-2 mb-1">Riwayat Alergi</div>
                    @if ($patient->allergies)
                        <div class="alert alert-warning py-2 px-3 mb-0 small">
                            <i class="bi bi-exclamation-triangle me-1"></i>{{ $patient->allergies }}
                        </div>
                    @else
                        <div class="text-muted-2">Tidak tercatat</div>
                    @endif
                </div>
            </x-card>
        </div>

        <div class="col-xl-8">
            <x-card title="Riwayat Pemeriksaan" icon="bi-journal-medical" class="mb-3">
                <ul class="list-unstyled mb-0">
                    @forelse ($records as $record)
                        <li class="py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="min-w-0">
                                    <div class="small fw-semibold">{{ $record->doctor->name ?? '-' }}</div>
                                    <div class="fs-7 text-muted-2">
                                        {{ $record->record_number }} &middot;
                                        {{ $record->examined_at?->translatedFormat('d M Y H:i') }}
                                    </div>
                                    <div class="small text-muted-2 mt-1">
                                        <strong>Keluhan:</strong> {{ $record->chief_complaint ?? '-' }}
                                    </div>
                                    @if ($record->diagnosis)
                                        <div class="small text-muted-2">
                                            <strong>Diagnosis:</strong> {{ $record->diagnosis }}
                                        </div>
                                    @endif
                                </div>
                                @if ($record->prescription)
                                    <span class="badge text-bg-primary">Ada resep</span>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li><x-empty-state icon="bi-journal-medical" title="Belum ada rekam medis" /></li>
                    @endforelse
                </ul>
            </x-card>

            <x-card title="Riwayat Resep" icon="bi-file-earmark-medical" class="mb-3">
                <ul class="list-unstyled mb-0">
                    @forelse ($prescriptions as $prescription)
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="small fw-semibold">{{ $prescription->code }}</div>
                                <div class="fs-7 text-muted-2">
                                    {{ $prescription->doctor?->name ?? '-' }} &middot;
                                    {{ $prescription->created_at?->translatedFormat('d M Y') }}
                                </div>
                            </div>
                            <x-badge :text="$prescription->statusLabel()"
                                    :color="match ($prescription->status) {
                                        'COMPLETED' => 'success',
                                        'CANCELLED' => 'danger',
                                        'READY' => 'info',
                                        'PROCESSING' => 'warning',
                                        default => 'secondary',
                                    }" />
                        </li>
                    @empty
                        <li><x-empty-state icon="bi-file-earmark-medical" title="Belum ada resep" /></li>
                    @endforelse
                </ul>
            </x-card>

            <x-card title="Riwayat Antrean" icon="bi-list-ol">
                <ul class="list-unstyled mb-0">
                    @forelse ($queues as $queue)
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge text-bg-primary">{{ $queue->queue_number }}</span>
                                <div>
                                    <div class="small">{{ $queue->doctor->name }}</div>
                                    <div class="fs-7 text-muted-2">{{ $queue->queue_date?->translatedFormat('d M Y') }}</div>
                                </div>
                            </div>
                            <x-status-badge :status="$queue->status" />
                        </li>
                    @empty
                        <li><x-empty-state icon="bi-list-ol" title="Belum ada antrean" /></li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
@endsection